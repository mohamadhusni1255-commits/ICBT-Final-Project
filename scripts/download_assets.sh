#!/bin/bash

# TalentUp Sri Lanka - Asset Download Script
# Downloads optimized images for the platform

echo "🎭 TalentUp Sri Lanka - Downloading Assets"
echo "========================================"

# Create directories
mkdir -p assets/images
cd assets/images

# Function to create placeholder images using ImageMagick (if available)
create_placeholder() {
    local filename=$1
    local width=$2
    local height=$3
    local text=$4
    
    if command -v convert &> /dev/null; then
        convert -size ${width}x${height} xc:'#3B82F6' \
                -gravity center \
                -fill white \
                -pointsize 24 \
                -annotate 0 "$text" \
                "$filename"
        echo "✅ Created placeholder: $filename"
    else
        echo "⚠️  ImageMagick not found. Please manually add: $filename ($width x $height)"
    fi
}

# Function to create SVG placeholder
create_svg_placeholder() {
    local filename=$1
    local icon=$2
    
    cat > "$filename" << EOF
<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <rect width="100" height="100" fill="#3B82F6" rx="10"/>
    <text x="50" y="55" text-anchor="middle" fill="white" font-size="40">$icon</text>
</svg>
EOF
    echo "✅ Created SVG: $filename"
}

echo "📥 Downloading/Creating placeholder images..."

# Hero images (create placeholders - replace with actual images)
create_placeholder "hero-talent-show.jpg" 1920 1080 "TalentUp Hero"
create_placeholder "hero-talent-show-thumb.jpg" 480 270 "Hero Thumb"

# Video placeholders
create_placeholder "placeholder-video.jpg" 854 480 "Video Placeholder"
create_placeholder "placeholder-video-thumb.jpg" 480 270 "Video Thumb"

# Create SVG icons
create_svg_placeholder "icon-upload.svg" "📤"
create_svg_placeholder "icon-judge.svg" "👨‍⚖️"
create_svg_placeholder "icon-trophy.svg" "🏆"
create_svg_placeholder "icon-mobile.svg" "📱"
create_svg_placeholder "icon-secure.svg" "🔒"
create_svg_placeholder "icon-multilingual.svg" "🌐"

# User interface elements
create_placeholder "avatar-placeholder.png" 100 100 "Avatar"
create_placeholder "logo-square.png" 256 256 "TU"

# Create favicon (if ImageMagick available)
if command -v convert &> /dev/null; then
    convert -size 32x32 xc:'#3B82F6' \
            -gravity center \
            -fill white \
            -pointsize 20 \
            -annotate 0 "T" \
            favicon.ico
    echo "✅ Created favicon.ico"
fi

echo ""
echo "🎨 Asset creation completed!"
echo ""
echo "📝 MANUAL REPLACEMENT RECOMMENDED:"
echo "=================================="
echo "1. Replace hero-talent-show.jpg with actual talent show image"
echo "2. Replace placeholder-video.jpg with default video thumbnail"
echo "3. Use professional icons instead of emoji placeholders"
echo "4. Add proper logo files"
echo ""
echo "💡 Recommended sources:"
echo "- Pexels.com (free images)"
echo "- Unsplash.com (free images)"
echo "- Feathericons.com (free SVG icons)"
echo "- Heroicons.com (free SVG icons)"
echo ""
echo "🔧 For production, run image optimization:"
echo "- Use tools like TinyPNG for compression"
echo "- Convert to WebP format for better performance"
echo "- Ensure all images are optimized for web"

cd ../../