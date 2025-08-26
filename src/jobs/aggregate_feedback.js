const FeedbackModel = require('../models/FeedbackModel');
const FeedbackSummaryModel = require('../models/FeedbackSummaryModel');
const { v4: uuidv4 } = require('uuid');

/**
 * Aggregate feedback job
 * Processes judge feedback to create video summaries and categories
 * Based ONLY on judge feedback - NO AI analysis
 */

class FeedbackAggregator {
  
  // Main aggregation function
  static async runAggregation() {
    console.log('Starting feedback aggregation...');
    
    try {
      // Get videos with feedback averages
      const videoAverages = await FeedbackModel.getVideoAverages();
      
      let processed = 0;
      let updated = 0;
      
      for (const video of videoAverages) {
        try {
          await this.processVideoFeedback(video);
          processed++;
          
          // Check if summary was created/updated
          const summary = await FeedbackSummaryModel.findByVideoId(video.video_id);
          if (summary) updated++;
          
        } catch (error) {
          console.error(`Error processing video ${video.video_id}:`, error);
        }
      }
      
      console.log(`Aggregation completed. Processed: ${processed}, Updated: ${updated}`);
      return { processed, updated };
      
    } catch (error) {
      console.error('Feedback aggregation failed:', error);
      throw error;
    }
  }
  
  // Process individual video feedback
  static async processVideoFeedback(videoData) {
    const { video_id, avg_voice, avg_creativity, avg_presentation, feedback_count } = videoData;
    
    if (feedback_count === 0) {
      return; // No feedback to process
    }
    
    // Get individual feedback comments for aggregation
    const feedback = await FeedbackModel.findByVideoId(video_id);
    
    // Aggregate text from comments
    const aggregatedText = this.aggregateComments(feedback);
    
    // Generate category label based on score patterns
    const categoryLabel = this.generateCategoryLabel(
      parseFloat(avg_voice),
      parseFloat(avg_creativity),
      parseFloat(avg_presentation)
    );
    
    // Create/update summary
    const summaryData = {
      video_id,
      avg_voice: parseFloat(avg_voice),
      avg_creativity: parseFloat(avg_creativity),
      avg_presentation: parseFloat(avg_presentation),
      aggregated_text: aggregatedText,
      category_label: categoryLabel,
      updated_at: new Date().toISOString()
    };
    
    // Check if summary exists
    const existingSummary = await FeedbackSummaryModel.findByVideoId(video_id);
    
    if (existingSummary) {
      await FeedbackSummaryModel.update(video_id, summaryData);
    } else {
      await FeedbackSummaryModel.create({
        id: uuidv4(),
        ...summaryData
      });
    }
  }
  
  // Aggregate comments into readable summary
  static aggregateComments(feedbackList) {
    if (!feedbackList || feedbackList.length === 0) {
      return 'No feedback available yet.';
    }
    
    // Get top 2 judge comments (by length/quality)
    const validComments = feedbackList
      .filter(f => f.comments && f.comments.trim().length > 10)
      .sort((a, b) => b.comments.length - a.comments.length)
      .slice(0, 2);
    
    if (validComments.length === 0) {
      return 'Judges provided scores but no detailed comments.';
    }
    
    // Extract first two sentences from each comment
    const summaryParts = validComments.map(feedback => {
      const comment = this.stripHtml(feedback.comments);
      const sentences = comment.split(/[.!?]+/).filter(s => s.trim().length > 5);
      return sentences.slice(0, 2).join('. ').trim() + '.';
    });
    
    // Deduplicate and combine
    const uniqueSummaries = [...new Set(summaryParts)];
    return uniqueSummaries.join(' ');
  }
  
  // Generate category label based on exact rules
  static generateCategoryLabel(avgVoice, avgCreativity, avgPresentation) {
    const labels = [];
    
    // EXACT rule set as specified
    if (avgVoice >= 8 && avgPresentation >= 7) {
      labels.push('Strong Voice & Presentation');
    } else if (avgVoice >= 8) {
      labels.push('Strong Voice, Needs Presentation');
    }
    
    if (avgCreativity >= 8) {
      labels.push('Highly Creative');
    }
    
    // Additional categories based on patterns
    if (avgPresentation >= 8 && avgCreativity >= 7) {
      labels.push('Excellent Performance');
    }
    
    if (avgVoice >= 7 && avgCreativity >= 7 && avgPresentation >= 7) {
      labels.push('Well-Rounded Talent');
    }
    
    // Default case
    if (labels.length === 0) {
      labels.push('Needs Improvement');
    }
    
    return labels.join(', ');
  }
  
  // Strip HTML tags and clean text
  static stripHtml(html) {
    if (!html) return '';
    return html.replace(/<[^>]*>/g, '').trim();
  }
}

// CLI execution support
if (require.main === module) {
  FeedbackAggregator.runAggregation()
    .then(result => {
      console.log('Aggregation result:', result);
      process.exit(0);
    })
    .catch(error => {
      console.error('Aggregation failed:', error);
      process.exit(1);
    });
}

module.exports = FeedbackAggregator;