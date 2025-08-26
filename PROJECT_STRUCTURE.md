# TalentUp Sri Lanka - Project File Tree

```
talentup-srilanka/
├── README.md
├── .env.example
├── .gitignore
├── package.json
├── server.js
├── migrations/
│   └── 2025_01_15_create_tables.sql
├── sample_seed.sql
├── src/
│   ├── config.js
│   ├── controllers/
│   │   ├── AuthController.js
│   │   ├── VideoController.js
│   │   ├── FeedbackController.js
│   │   └── AdminController.js
│   ├── models/
│   │   ├── UserModel.js
│   │   ├── VideoModel.js
│   │   ├── FeedbackModel.js
│   │   ├── LikeModel.js
│   │   ├── CompetitionModel.js
│   │   └── FeedbackSummaryModel.js
│   ├── services/
│   │   └── SupabaseService.js
│   ├── middleware/
│   │   ├── auth.js
│   │   ├── csrf.js
│   │   └── roleCheck.js
│   └── jobs/
│       └── aggregate_feedback.js
├── php_api/
│   ├── auth_login.php
│   ├── auth_register.php
│   ├── upload_video.php
│   ├── post_feedback.php
│   └── aggregate_feedback.php
├── public/
│   ├── index.html
│   ├── register.html
│   ├── login.html
│   ├── dashboard_user.html
│   ├── video_list.html
│   ├── video_detail.html
│   ├── judge_panel.html
│   └── admin_panel.html
├── lang/
│   ├── en.json
│   ├── si.json
│   └── ta.json
├── assets/
│   └── images/
│       ├── manifest.txt
│       ├── hero-talent-show.jpg
│       ├── hero-talent-show-thumb.jpg
│       ├── placeholder-video.jpg
│       └── placeholder-video-thumb.jpg
├── scripts/
│   └── download_assets.sh
├── API_DOCS.md
└── SUPABASE_STORAGE_GUIDE.md
```