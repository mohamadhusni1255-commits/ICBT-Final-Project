/**
 * Judge Panel Fixes
 * Connects all judge panel buttons and replaces fake data with live data without modifying HTML/CSS
 */

class JudgeFixes {
    constructor() {
        this.baseUrl = window.location.origin;
        this.csrfToken = null;
        this.currentUser = null;
        this.init();
    }

    init() {
        this.loadCSRFToken();
        this.checkAuth();
        this.connectJudgeButtons();
        this.loadJudgeData();
        this.setupEvaluationForms();
    }

    async loadCSRFToken() {
        try {
            const response = await fetch(`${this.baseUrl}/api/auth/csrf-token`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.csrfToken = data.token;
                    console.log('Judge CSRF token loaded');
                }
            }
        } catch (error) {
            console.error('Failed to load judge CSRF token:', error);
        }
    }

    async checkAuth() {
        try {
            const response = await fetch(`${this.baseUrl}/api/auth/me`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.user) {
                    this.currentUser = data.user;
                    
                    // Check if user has judge/admin role
                    if (data.user.role !== 'judge' && data.user.role !== 'admin') {
                        this.showError('Access denied. Judge role required.');
                        setTimeout(() => {
                            window.location.href = 'dashboard_user.html';
                        }, 2000);
                        return;
                    }
                } else {
                    // Not logged in, redirect to login
                    window.location.href = 'login.html';
                    return;
                }
            } else {
                // Not logged in, redirect to login
                window.location.href = 'login.html';
                return;
            }
        } catch (error) {
            console.error('Auth check failed:', error);
            window.location.href = 'login.html';
            return;
        }
    }

    connectJudgeButtons() {
        // Connect tab buttons
        this.connectTabButtons();
        
        // Connect evaluation buttons
        this.connectEvaluationButtons();
        
        // Connect export buttons
        this.connectExportButtons();
        
        // Connect bulk action buttons
        this.connectBulkActionButtons();
    }

    connectTabButtons() {
        document.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(tab);
            });
        });
    }

    connectEvaluationButtons() {
        // Connect submit evaluation buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.submit-evaluation-btn')) {
                const btn = e.target.closest('.submit-evaluation-btn');
                const videoId = btn.getAttribute('data-video-id');
                if (videoId) {
                    this.handleSubmitEvaluation(videoId, btn);
                }
            }
        });

        // Connect approve/reject buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.approve-btn')) {
                const btn = e.target.closest('.approve-btn');
                const videoId = btn.getAttribute('data-video-id');
                if (videoId) {
                    this.handleApproveVideo(videoId);
                }
            }
            
            if (e.target.closest('.reject-btn')) {
                const btn = e.target.closest('.reject-btn');
                const videoId = btn.getAttribute('data-video-id');
                if (videoId) {
                    this.handleRejectVideo(videoId);
                }
            }
        });
    }

    connectExportButtons() {
        // Connect export scores button
        const exportScoresBtn = this.findButtonByText('Export Scores');
        if (exportScoresBtn) {
            exportScoresBtn.addEventListener('click', () => this.exportScores());
        }

        // Connect export evaluations button
        const exportEvaluationsBtn = this.findButtonByText('Export Evaluations');
        if (exportEvaluationsBtn) {
            exportEvaluationsBtn.addEventListener('click', () => this.exportEvaluations());
        }
    }

    connectBulkActionButtons() {
        // Connect bulk approve/reject buttons
        const bulkApproveBtn = this.findButtonByText('Bulk Approve');
        if (bulkApproveBtn) {
            bulkApproveBtn.addEventListener('click', () => this.handleBulkAction('approve'));
        }

        const bulkRejectBtn = this.findButtonByText('Bulk Reject');
        if (bulkRejectBtn) {
            bulkRejectBtn.addEventListener('click', () => this.handleBulkAction('reject'));
        }
    }

    switchTab(clickedTab) {
        // Remove active class from all tabs
        document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
        
        // Add active class to clicked tab
        clickedTab.classList.add('active');
        
        // Load content based on the tab
        const tabName = clickedTab.textContent.toLowerCase();
        this.loadTabContent(tabName);
    }

    async loadTabContent(tabName) {
        switch (tabName) {
            case 'pending':
                await this.loadPendingVideos();
                break;
            case 'evaluated':
                await this.loadEvaluatedVideos();
                break;
            case 'drafts':
                this.loadDraftEvaluations();
                break;
            case 'analytics':
                await this.loadJudgeAnalytics();
                break;
        }
    }

    async loadJudgeData() {
        try {
            const response = await fetch(`${this.baseUrl}/api/judge/panel`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.updateJudgeStats(data.judge_stats);
                    this.displayPendingVideos(data.pending_videos);
                }
            }
        } catch (error) {
            console.error('Failed to load judge data:', error);
        }
    }

    async loadPendingVideos() {
        try {
            const response = await fetch(`${this.baseUrl}/api/judge/videos_to_review`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.displayPendingVideos(data.videos);
                }
            }
        } catch (error) {
            console.error('Failed to load pending videos:', error);
        }
    }

    async loadEvaluatedVideos() {
        try {
            const response = await fetch(`${this.baseUrl}/api/judge/evaluated_videos`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.displayEvaluatedVideos(data.videos);
                }
            }
        } catch (error) {
            console.error('Failed to load evaluated videos:', error);
        }
    }

    loadDraftEvaluations() {
        // Load draft evaluations from localStorage
        const drafts = this.getDraftEvaluations();
        this.displayDraftEvaluations(drafts);
    }

    async loadJudgeAnalytics() {
        try {
            const response = await fetch(`${this.baseUrl}/api/judge/analytics`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.displayJudgeAnalytics(data.analytics);
                }
            }
        } catch (error) {
            console.error('Failed to load judge analytics:', error);
        }
    }

    updateJudgeStats(stats) {
        // Update statistics display
        const statElements = {
            'totalEvaluated': document.querySelector('[data-stat="total-evaluated"]'),
            'averageRating': document.querySelector('[data-stat="average-rating"]'),
            'pendingVideos': document.querySelector('[data-stat="pending-videos"]'),
            'completionRate': document.querySelector('[data-stat="completion-rate"]')
        };

        if (stats) {
            Object.keys(statElements).forEach(key => {
                const element = statElements[key];
                if (element && stats[key] !== undefined) {
                    element.textContent = stats[key];
                }
            });
        }
    }

    displayPendingVideos(videos) {
        const container = document.querySelector('.pending-videos, .videos-container');
        if (!container) return;

        if (!videos || videos.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-video"></i></div>
                    <h3 class="empty-title">No Videos Pending</h3>
                    <p class="empty-description">All videos have been evaluated!</p>
                </div>
            `;
            return;
        }

        // Replace fake data with real data
        container.innerHTML = videos.map(video => `
            <div class="video-card" data-video-id="${video.id}">
                <div class="video-thumbnail">
                    <img src="${video.thumbnail_url || '/assets/placeholder-thumbnail.jpg'}" alt="${video.title}">
                </div>
                <div class="video-info">
                    <h4 class="video-title">${video.title}</h4>
                    <p class="video-description">${video.description || 'No description'}</p>
                    <div class="video-meta">
                        <span class="uploader">${video.uploader_name || 'Unknown'}</span>
                        <span class="duration">${video.duration || '0:00'}</span>
                        <span class="category">${video.category || 'Uncategorized'}</span>
                    </div>
                </div>
                <div class="video-actions">
                    <button class="btn btn-primary watch-btn" onclick="judgeFixes.watchVideo(${video.id})">
                        <i class="fas fa-play"></i> Watch
                    </button>
                    <button class="btn btn-success approve-btn" data-video-id="${video.id}">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-danger reject-btn" data-video-id="${video.id}">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            </div>
        `).join('');
    }

    displayEvaluatedVideos(videos) {
        const container = document.querySelector('.evaluated-videos, .videos-container');
        if (!container) return;

        if (!videos || videos.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-check-circle"></i></div>
                    <h3 class="empty-title">No Videos Evaluated Yet</h3>
                    <p class="empty-description">Start evaluating pending videos!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = videos.map(video => `
            <div class="video-card evaluated" data-video-id="${video.id}">
                <div class="video-thumbnail">
                    <img src="${video.thumbnail_url || '/assets/placeholder-thumbnail.jpg'}" alt="${video.title}">
                </div>
                <div class="video-info">
                    <h4 class="video-title">${video.title}</h4>
                    <p class="video-description">${video.description || 'No description'}</p>
                    <div class="video-meta">
                        <span class="uploader">${video.uploader_name || 'Unknown'}</span>
                        <span class="rating">Rating: ${video.average_rating || 'N/A'}/10</span>
                        <span class="evaluated-date">${this.formatDate(video.evaluated_at)}</span>
                    </div>
                </div>
                <div class="video-actions">
                    <button class="btn btn-outline view-evaluation-btn" onclick="judgeFixes.viewEvaluation(${video.id})">
                        <i class="fas fa-eye"></i> View Evaluation
                    </button>
                </div>
            </div>
        `).join('');
    }

    displayDraftEvaluations(drafts) {
        const container = document.querySelector('.draft-evaluations, .videos-container');
        if (!container) return;

        if (!drafts || drafts.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-edit"></i></div>
                    <h3 class="empty-title">No Draft Evaluations</h3>
                    <p class="empty-description">All evaluations have been submitted!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = drafts.map(draft => `
            <div class="draft-card" data-video-id="${draft.video_id}">
                <h4>${draft.video_title || 'Untitled Video'}</h4>
                <p>Draft created: ${this.formatDate(draft.created_at)}</p>
                <div class="draft-actions">
                    <button class="btn btn-primary" onclick="judgeFixes.continueDraft(${draft.video_id})">
                        Continue Draft
                    </button>
                    <button class="btn btn-danger" onclick="judgeFixes.deleteDraft(${draft.video_id})">
                        Delete Draft
                    </button>
                </div>
            </div>
        `).join('');
    }

    displayJudgeAnalytics(analytics) {
        const container = document.querySelector('.judge-analytics, .analytics-container');
        if (!container) return;

        if (!analytics) {
            container.innerHTML = '<p>No analytics data available</p>';
            return;
        }

        container.innerHTML = `
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h4>Evaluation Speed</h4>
                    <p>${analytics.avg_evaluation_time || 'N/A'} minutes per video</p>
                </div>
                <div class="analytics-card">
                    <h4>Consistency Score</h4>
                    <p>${analytics.consistency_score || 'N/A'}%</p>
                </div>
                <div class="analytics-card">
                    <h4>Total Evaluations</h4>
                    <p>${analytics.total_evaluations || 0}</p>
                </div>
            </div>
        `;
    }

    setupEvaluationForms() {
        // Create evaluation forms dynamically for each video
        this.createEvaluationForms();
    }

    createEvaluationForms() {
        // This will be called when videos are loaded
        // Forms will be created dynamically for each video
    }

    async handleSubmitEvaluation(videoId, button) {
        const form = button.closest('.evaluation-form');
        if (!form) return;

        const formData = new FormData(form);
        const submitBtn = button;
        const originalText = submitBtn.innerHTML;

        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const evaluationData = {
                video_id: videoId,
                technical_score: parseInt(formData.get('technical_score') || 5),
                creativity_score: parseInt(formData.get('creativity_score') || 5),
                presentation_score: parseInt(formData.get('presentation_score') || 5),
                feedback: formData.get('feedback') || '',
                rating: this.calculateAverageScore({
                    technical: parseInt(formData.get('technical_score') || 5),
                    creativity: parseInt(formData.get('creativity_score') || 5),
                    presentation: parseInt(formData.get('presentation_score') || 5)
                })
            };

            const response = await fetch(`${this.baseUrl}/api/judge/submit_feedback`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(evaluationData)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showSuccess('Evaluation submitted successfully!');
                form.reset();
                
                // Remove draft from localStorage
                this.removeDraftEvaluation(videoId);
                
                // Refresh data
                this.loadJudgeData();
            } else {
                this.showError(data.error || 'Failed to submit evaluation');
            }
        } catch (error) {
            console.error('Evaluation submission failed:', error);
            this.showError('Network error. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    async handleApproveVideo(videoId) {
        if (!confirm('Are you sure you want to approve this video?')) {
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}/api/judge/approve_video`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ video_id: videoId })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showSuccess('Video approved successfully!');
                    this.loadJudgeData(); // Refresh
                } else {
                    this.showError(data.error || 'Failed to approve video');
                }
            }
        } catch (error) {
            console.error('Approve video error:', error);
            this.showError('Network error. Please try again.');
        }
    }

    async handleRejectVideo(videoId) {
        const reason = prompt('Please provide a reason for rejection:');
        if (!reason) return;

        try {
            const response = await fetch(`${this.baseUrl}/api/judge/reject_video`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ 
                    video_id: videoId,
                    reason: reason
                })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showSuccess('Video rejected successfully!');
                    this.loadJudgeData(); // Refresh
                } else {
                    this.showError(data.error || 'Failed to reject video');
                }
            }
        } catch (error) {
            console.error('Reject video error:', error);
            this.showError('Network error. Please try again.');
        }
    }

    async handleBulkAction(action) {
        const selectedVideos = this.getSelectedVideos();
        
        if (selectedVideos.length === 0) {
            this.showError('Please select videos first');
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}/api/judge/bulk_action`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    action: action,
                    video_ids: selectedVideos
                })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showSuccess(`Bulk ${action} completed successfully!`);
                    this.loadJudgeData(); // Refresh
                } else {
                    this.showError(data.error || `Bulk ${action} failed`);
                }
            }
        } catch (error) {
            console.error(`Bulk ${action} error:`, error);
            this.showError('Network error. Please try again.');
        }
    }

    async exportScores() {
        try {
            const response = await fetch(`${this.baseUrl}/api/judge/export_scores`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `judge_scores_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
        } catch (error) {
            console.error('Export scores error:', error);
            this.showError('Failed to export scores');
        }
    }

    async exportEvaluations() {
        try {
            const response = await fetch(`${this.baseUrl}/api/judge/export_evaluations`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `judge_evaluations_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
        } catch (error) {
            console.error('Export evaluations error:', error);
            this.showError('Failed to export evaluations');
        }
    }

    // Utility methods
    findButtonByText(text) {
        const buttons = Array.from(document.querySelectorAll('button'));
        return buttons.find(btn => btn.textContent.trim() === text);
    }

    calculateAverageScore(scores) {
        const values = Object.values(scores);
        return values.reduce((sum, score) => sum + score, 0) / values.length;
    }

    getSelectedVideos() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    getDraftEvaluations() {
        const drafts = localStorage.getItem('judge_draft_evaluations');
        return drafts ? JSON.parse(drafts) : [];
    }

    removeDraftEvaluation(videoId) {
        const drafts = this.getDraftEvaluations();
        const filtered = drafts.filter(d => d.video_id !== videoId);
        localStorage.setItem('judge_draft_evaluations', JSON.stringify(filtered));
    }

    formatDate(dateString) {
        if (!dateString) return 'Unknown';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    watchVideo(videoId) {
        // Open video in new tab or modal
        window.open(`video_detail.html?id=${videoId}`, '_blank');
    }

    viewEvaluation(videoId) {
        // Show evaluation details
        console.log('View evaluation for video:', videoId);
    }

    continueDraft(videoId) {
        // Continue working on draft evaluation
        console.log('Continue draft for video:', videoId);
    }

    deleteDraft(videoId) {
        if (confirm('Are you sure you want to delete this draft?')) {
            this.removeDraftEvaluation(videoId);
            this.loadDraftEvaluations();
        }
    }

    showSuccess(message) {
        alert(`Success: ${message}`);
    }

    showError(message) {
        alert(`Error: ${message}`);
    }
}

// Initialize judge fixes when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname.includes('judge_panel')) {
        window.judgeFixes = new JudgeFixes();
    }
});
