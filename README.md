**kellgradeschool.com/**  
│  
├── Home (/)  
│ ├── Hero Image (school front) with school mascot (“Kell Indians”), colors (Royal Blue, White, Black)  
│ ├── Live Feed [Secretary-maintained announcements — see note A]  
│ ├── Facebook feed embed [Config: facebook_page_id]  
│ ├── Google Calendar widget (upcoming events) [Config: google_calendar_id]  
│ ├── Weather widget — current conditions + 7-day forecast (Kell, IL)  
│ ├── Illinois Report Card prominent link (District IIRC) [105 ILCS 5/10-17a]  
│ ├── Quick action buttons: Lunch Menu | Staff Directory | Calendar | Report Card  
│ ├── “Why Our School” / Pride section (Academic Excellence, Athletics, Community, etc.)  
│ └── Latest News teasers (3-4) with “View All” link  
│  
├── About (/about/)  
│ ├── About the District [Config: about_text_doc_id] (editable text + photo gallery)  
│ ├── School Board (/about/school-board/)  
│ │ ├── Schedule of Regular Board Meetings (Dates, Times, Locations) [5 ILCS 120/2.02]  
│ │ ├── Agendas for Board Meetings [5 ILCS 120/2.02]  
│ │ ├── Minutes from Past Meetings (at least 60 days) [5 ILCS 120/2.06]  
│ │ ├── List of All Board Members & Leadership Training Completion [105 ILCS 5/10-16a]  
│ │ ├── Board Member Contact Emails (School Accounts) [50 ILCS 205/20]  
│ │ └── Board Documents — JS Document Tree [Config: board_docs_folder_id]  
│ ├── District Documents (/about/documents/)  
│ │ ├── Current Itemized Annual Budget [105 ILCS 5/17-1.2]  
│ │ ├── All Contracts over $25,000 [105 ILCS 5/10-20.44]  
│ │ ├── Collective Bargaining Agreements [105 ILCS 5/10-20.44]  
│ │ ├── Administrator & Teacher Compensation Reports [105 ILCS 5/10-20.47]  
│ │ ├── IMRF Employees Compensated Over $75,000 [105 ILCS 120/7.3]  
│ │ ├── Sexual Harassment Severance Agreements (if applicable) [50 ILCS 205/3C]  
│ │ ├── FOIA Officer Information [5 ILCS 140/4]  
│ │ ├── Contract for Third Party Driver’s Ed Provider (if applicable) [105 ILCS 5/27-24.2]  
│ │ └── JS Document Tree [Config: legal_docs_folder_id]  
│ ├── Policies (/about/policies/)  
│ │ ├── Anti-Bias Education Policies [105 ILCS 5/27-23.6]  
│ │ ├── Bullying Prevention Policy [105 ILCS 5/27-23.7]  
│ │ ├── Suicide Awareness and Prevention Policy + Resources [105 ILCS 5/2-3.166]  
│ │ ├── Sexual Abuse Policy – Faith’s Law [105 ILCS 5/22-85.5]  
│ │ ├── SOPPA Policy (Parent Privacy) [105 ILCS 85/27]  
│ │ ├── Code of Professional Conduct Policy [5:120]  
│ │ └── Special Education & Section 504 Plans Information [105 ILCS 5/14-6.01]  
│ ├── Building Information  
│ ├── Employment (/about/employment/)  
│ │ └── Vacancy Notices [Config: employment_sheet_id]  
│ └── Staff Directory (/about/staff-directory/)  
│     └── Searchable staff table — photos, roles, emails, phone if public, personal page links [Config: staff_sheet_id]  
│  
├── Academics (/academics/)  
│ ├── Grade School  
│ │ ├── Preschool / Pre-K [Config: prek_site_url]  
│ │ ├── Kindergarten (/academics/kindergarten/) [Config: ks_site_url]  
│ │ ├── 1st Grade (/academics/1st-grade/) [Config: gr1_site_url]  
│ │ ├── 2nd Grade (/academics/2nd-grade/) [Config: gr2_site_url]  
│ │ ├── 3rd Grade (/academics/3rd-grade/) [Config: gr3_site_url]  
│ │ ├── 4th Grade (/academics/4th-grade/) [Config: gr4_site_url]  
│ │ └── 5th Grade (/academics/5th-grade/) [Config: gr5_site_url]  
│ ├── Junior High  
│ │ ├── English Language Arts (/academics/jr-high-english/) [Config: jh_ela_site_url]  
│ │ ├── Math (/academics/jr-high-math/) [Config: jh_math_site_url]  
│ │ ├── Science (/academics/jr-high-science/) [Config: jh_science_site_url]  
│ │ └── Social Studies (/academics/jr-high-social-studies/) [Config: jh_ss_site_url]  
│ ├── Schoolwide Programs  
│ │ ├── Special Education (/academics/special-education/) [Config: sped_site_url]  
│ │ └── Title I (/academics/title-i/) [Config: title1_site_url]  
│ └── Useful Links Hub — 40+ sites organized by subject  
│     ├── Math (Khan Academy, IXL, Desmos, Prodigy, etc.)  
│     ├── Science (NASA Kids, Mystery Science, PhET, etc.)  
│     ├── Social Studies (iCivics, DocsTeach, Time for Kids, etc.)  
│     ├── ELA (AR, CommonLit, ReadWorks, NoRedInk, etc.)  
│     └── General (Google Classroom, Kahoot, Quizlet, CK-12, etc.)  
│     (Handbooks, supply lists, and curriculum overviews also located here)  
│  
├── Calendar (/calendar/)  
│ ├── Monthly calendar image [Config: monthly_cal_file_id]  
│ ├── Academic year calendar image [Config: academic_cal_file_id]  
│ └── Full Google Calendar embed — agenda view [Config: google_calendar_id]  
│  
├── Dining (/dining/)  
│ ├── Breakfast menu image or PDF [Config: breakfast_menu_file_id]  
│ ├── Lunch menu image or PDF [Config: lunch_menu_file_id]  
│ ├── Free Breakfast/Lunch info (Illinois program)  
│ └── Nutrition guidelines / forms  
│  
├── News (/news/)  
│ └── Live Feed [Same as Home — see note A]  
│  
├── Activities (/activities/)  
│ ├── Sports  
│ │ ├── Baseball (/activities/baseball/) [Config: baseball_site_url]  
│ │ ├── Basketball (/activities/basketball/) [Config: basketball_site_url]  
│ │ ├── Bowling (/activities/bowling/) [Config: bowling_site_url]  
│ │ ├── Cheerleading (/activities/cheerleading/) [Config: cheer_site_url]  
│ │ ├── Cross Country (/activities/cross-country/) [Config: xc_site_url]  
│ │ └── Volleyball (/activities/volleyball/) [Config: volleyball_site_url]  
│ └── Clubs  
│     ├── Student Council (/activities/student-council/) [Config: stuco_site_url]  
│     ├── Yearbook (/activities/yearbook/) [Config: yearbook_site_url]  
│     ├── Book Club (/activities/book-club/) [Config: bookclub_site_url]  
│     ├── Cooking Club (/activities/cooking/) [Config: cooking_site_url]  
│     ├── Brain Games (/activities/brain-games/) [Config: braingames_site_url]  
│     └── Scholar Bowl (/activities/scholar-bowl/) [Config: scholarbowl_site_url]  
│  
└── Family (/family/)  
    ├── PTO (/family/pto/) [Config: pto_site_url — see note B]  
    ├── Registration / Enrollment forms  
    ├── Student Handbook  
    ├── Parent Portals (Google Classroom, etc.)  
    ├── Mass Communication info (phone/text opt-in)  
    ├── McKinney-Vento / Homeless Liaison  
    ├── ESSER / Funding transparency  
    ├── Health / Immunization policies  
    ├── Type 1 Diabetes Information for Parents [PA103-0641]  
    └── School FAFSA Contact [PA 104-0013]  

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  
**SITEWIDE ELEMENTS** (appear on every page)  
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  

├── Top Contact Bar (Address • Phone • Email + **TeacherEase Login**)  
├── Quick Links Bar: Staff Directory | Employment | Documents | Calendar | Dining | Report Card  
├── Office Contact Block [Config: contact_sheet_id]  
│   ├── Principal name  
│   ├── Secretary name  
│   ├── Address  
│   ├── Phone  
│   ├── Fax  
│   └── Email  
├── Facebook link [Config: facebook_page_id]  
├── Safety & Resources Footer Block  
│   ├── ABLE (ablenrc.org) [PA 104-0314]  
│   ├── Illinois Suicide Prevention (safe2helpil.com)  
│   ├── National Suicide Prevention (988lifeline.org) with Live Chat  
│   └── Notice of Automated Traffic Cameras on Buses [PA 098-0556]  

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  
**NOTES**  
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  

**A — LIVE FEED**  
No solution chosen yet. Desired behavior: secretary posts short, informal updates that appear on Home and News pages in real time. Recommended: Google Form → Sheet → displayed on site.

**B — GOOGLE SITES PASSTHROUGH PATTERN**  
Each sub-page in Academics, Activities, and Family has a corresponding config key.  
- If config value is set → display link/button/embed to Google Site.  
- If config value is empty → hide nav link and page entirely.


///////////////////////////////////////////////////////////////////////////

### Folder Structure

```
[ROOT]
│
├─ /cfg/
│   └─ config.php
│
├─ /kgs-core/                  (your existing core framework)
│   ├─ bootstrap.php
│   ├─ config/
│   │   ├─ modules.php
│   │   └─ google.php
│   └─ core/
│       ├─ ModuleManager.php
│       ├─ CacheManager.php
│       ├─ GoogleDriveClient.php
│       ├─ Router.php
│       └─ EventBus.php
│
├─ /kgs-modules/ 
│   └─ drive-tree/             (your existing document tree module)
│
├─ /kgs-cache/
│   └─ drive-tree/
│
└─ /public/ ← Web Root
    │
    ├── index.php                  ← Home
    ├── header.php
    ├── navigation.php
    ├── footer.php
    ├── api.php
    │
    ├── assets/
    │   └── css/
    │       └── style.css
    │
    ├── includes/
    │   ├── live-feed.php
    │   └── home/
    │       ├── hero.php
    │       ├── live-feed-section.php
    │       ├── widgets-section.php
    │       ├── sidebar.php
    │       └── pride-section.php          ← New (Why Our School section)
    │
    ├── about/                         ← Main About / District
    │   ├── index.php
    │   ├── school-board/
    │   │   ├── index.php
    │   │   ├── schedule.php
    │   │   ├── agenda.php
    │   │   └── minutes.php
    │   ├── documents/
    │   │   └── index.php                  ← Legal, Budget, Contracts, etc.
    │   ├── policies/
    │   │   └── index.php                  ← All required policies
    │   ├── employment/
    │   │   └── index.php
    │   └── staff-directory/
    │       └── index.php
    │
    ├── academics/
    │   └── index.php                      ← Grade School, Junior High, Useful Links, Handbooks
    │
    ├── calendar/
    │   └── index.php
    │
    ├── dining/
    │   └── index.php                      ← Menus + Free Meals Info
    │
    ├── news/
    │   └── index.php
    │
    ├── activities/                        ← Merged Sports + Clubs
    │   ├── index.php
    │   ├── baseball/
    │   │   └── index.php
    │   ├── basketball/
    │   │   └── index.php
    │   ├── bowling/
    │   │   └── index.php
    │   ├── cheerleading/
    │   │   └── index.php
    │   ├── cross-country/
    │   │   └── index.php
    │   ├── volleyball/
    │   │   └── index.php
    │   ├── student-council/
    │   │   └── index.php
    │   ├── yearbook/
    │   │   └── index.php
    │   ├── book-club/
    │   │   └── index.php
    │   ├── cooking/
    │   │   └── index.php
    │   ├── brain-games/
    │   │   └── index.php
    │   └── scholar-bowl/
    │       └── index.php
    │
    └── family/
        ├── index.php                      ← Registration, Handbooks, Portals, etc.
        └── pto/
            └── index.php
```

---



Final List of PHP Files Needed
Root Level (/public/)

index.php ← Main Home Page (modular)
header.php
navigation.php
footer.php
api.php (already exists)

Includes Folder

includes/live-feed.php
includes/home/hero.php
includes/home/live-feed-section.php
includes/home/widgets-section.php
includes/home/sidebar.php
includes/home/pride-section.php (Recommended - for "Why Our School" section)

About Section

about/index.php
about/school-board/index.php
about/school-board/schedule.php
about/school-board/agenda.php
about/school-board/minutes.php
about/documents/index.php
about/policies/index.php
about/employment/index.php
about/staff-directory/index.php

Main Sections

academics/index.php
calendar/index.php
dining/index.php
news/index.php
activities/index.php

Activities Sub-pages

activities/baseball/index.php
activities/basketball/index.php
activities/bowling/index.php
activities/cheerleading/index.php
activities/cross-country/index.php
activities/volleyball/index.php
activities/student-council/index.php
activities/yearbook/index.php
activities/book-club/index.php
activities/cooking/index.php
activities/brain-games/index.php
activities/scholar-bowl/index.php

Family Section

family/index.php
family/pto/index.php
