<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\CmsLoanProduct;
use App\Models\CmsSavingsProduct;
use App\Models\ContactInquiry;
use App\Models\Download;
use App\Models\Faq;
use App\Models\FooterSetting;
use App\Models\Gallery;
use App\Models\HomepageSection;
use App\Models\News;
use App\Models\Page;
use App\Models\SeoSetting;
use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Website Settings Initial Data
        WebsiteSetting::updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'TG Microfinance ERP',
                'phone' => '+91 (800) 555-0199',
                'email' => 'contact@tgmicrofinance.org',
                'address' => '123 Financial Plaza, Suite 400, Capital City',
                'social_links' => [
                    'facebook' => 'https://facebook.com/tgmicrofinance',
                    'twitter' => 'https://twitter.com/tgmicrofinance',
                    'linkedin' => 'https://linkedin.com/company/tgmicrofinance',
                    'instagram' => 'https://instagram.com/tgmicrofinance',
                    'youtube' => 'https://youtube.com/@tgmicrofinance',
                ],
                'footer_text' => '© ' . date('Y') . ' Astha Welfare Society. Developed By Tech Googly',
                'calc_enabled' => true,
                'calc_title' => 'Loan Rate Estimator',
                'calc_subtitle' => 'Instant repayment calculation',
                'calc_default_amount' => '50000',
                'calc_min_amount' => '5000',
                'calc_max_amount' => '500000',
                'calc_tenure_options' => ['6', '12', '18', '24', '36'],
                'calc_interest_rate' => '12.5%',
                'calc_type' => 'reducing_balance',
                'calc_rounding_type' => 'nearest_integer',
                'calc_cta_text' => 'Proceed with Application',
                'calc_cta_url' => '/apply-loan',
                'location_heading' => 'Headquarters & Branch Network',
                'location_description' => 'Visit any of our branch offices for counter disbursements, deposits, and officer guidance.',
                'support_box_title' => 'Direct Inquiries & Assistance',
                'support_box_desc' => 'Our team is available to guide you through loan applications and account setups.',
                'support_box_button_text' => 'Contact Support Team',
                'support_box_button_url' => '/contact',
            ]
        );

        // 2. Homepage Sections Initial Data
        $homepageSections = [
            [
                'section_key' => 'hero_section',
                'title' => 'Empowering Small Businesses & Communities',
                'subtitle' => 'Tailored Micro-Loans & Flexible Savings Plans',
                'description' => 'We provide accessible microfinance solutions designed to support entrepreneurs, women-led enterprises, and growing businesses across all regions.',
                'button_text' => 'Apply for Loan',
                'button_url' => '/apply-loan',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'section_key' => 'about_summary',
                'title' => 'About TG Microfinance',
                'subtitle' => 'Building Financial Independence',
                'description' => 'Founded with a mission to eliminate financial barriers, TG Microfinance serves thousands of members through transparent interest rates and digital collection services.',
                'button_text' => 'Read Our Story',
                'button_url' => '/about',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'section_key' => 'products_overview',
                'title' => 'Our Core Products',
                'subtitle' => 'Micro-Loans, Agri-Credit & Savings Accounts',
                'description' => 'Explore customized financial products engineered for high success rates, flexible repayments, and daily savings growth.',
                'button_text' => 'View All Products',
                'button_url' => '/products/loan',
                'status' => 'active',
                'sort_order' => 3,
            ],
            [
                'section_key' => 'headquarters_branch',
                'title' => 'Headquarters & Branch Network',
                'subtitle' => 'Physical Presence',
                'description' => 'Visit any of our branch offices for counter disbursements, deposits, and officer guidance.',
                'button_text' => 'Locate Branch',
                'button_url' => '/branches',
                'head_office_title' => 'TG Microfinance Headquarters',
                'address' => '100 Financial Avenue, Suite 500',
                'phone' => '+91 (800) 555-0199',
                'email' => 'info@tgmicrofinance.org',
                'support_box_title' => 'Direct Inquiries & Assistance',
                'support_box_description' => 'Our team is available to guide you through loan applications and account setups.',
                'support_button_text' => 'Contact Support Team',
                'support_button_url' => '/contact',
                'status' => 'active',
                'sort_order' => 4,
            ],
            [
                'section_key' => 'homepage_cta',
                'title' => 'Homepage CTA',
                'subtitle' => 'Call to Action',
                'description' => 'Final action section encouraging loan applications and customer support inquiries.',
                'cta_heading' => 'Ready to Apply for Micro-Credit?',
                'cta_description' => 'Submit your initial loan request online in minutes, or visit your nearest branch counter today.',
                'cta_button1_text' => 'Apply for Loan Now',
                'cta_button1_url' => '/apply-loan',
                'cta_button2_text' => 'Contact Customer Support',
                'cta_button2_url' => '/contact',
                'cta_bg_style' => 'primary',
                'status' => 'active',
                'sort_order' => 5,
            ],
        ];

        foreach ($homepageSections as $section) {
            HomepageSection::updateOrCreate(
                ['section_key' => $section['section_key']],
                array_merge($section, $section['section_key'] === 'about_summary' ? [
                    'governance_title' => 'Institutional Governance',
                    'governance_subtitle' => 'Regulated Micro-Finance ERP',
                    'governance_description' => 'Operating under central banking regulation and double-entry accounting integrity.',
                    'governance_bullets' => [
                        'Double-entry general ledger audited financial accounting',
                        'Field officer GPS biometric KYC identification',
                        'Central vault limit controls and instant digital receipts'
                    ],
                    'governance_icon' => 'bi-bank2',
                ] : [])
            );
        }

        // 3. Banners Initial Data
        $banners = [
            [
                'title' => 'Instant Business Micro-Loans Up to $10,000',
                'subtitle' => 'Low Interest Rates with Quick 24-Hour Approval',
                'button_text' => 'Apply Now',
                'button_url' => '/apply-loan',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => 'Secure High-Yield Savings Accounts',
                'subtitle' => 'Earn Up to 8.5% Annual Interest with Complete Capital Safety',
                'button_text' => 'Open Account',
                'button_url' => '/products/savings',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'title' => 'Digital Banking & Doorstep Collection',
                'subtitle' => 'Manage your savings and loan installments right from your mobile device',
                'button_text' => 'Explore Services',
                'button_url' => '/services/digital-banking',
                'status' => 'active',
                'sort_order' => 3,
            ],
        ];

        foreach ($banners as $bannerData) {
            Banner::firstOrCreate(
                ['title' => $bannerData['title']],
                $bannerData
            );
        }

        // 4. Pages CMS Initial Data
        $pages = [
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => '<h2>Terms of Service</h2><p>Welcome to TG Microfinance ERP. By accessing our platform, you agree to comply with our enterprise financial governance and compliance terms.</p>',
                'status' => 'published',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<h2>Privacy Policy</h2><p>Your privacy is our priority. We safeguard member data using banking-grade security standards and strict data confidentiality protocols.</p>',
                'status' => 'published',
            ],
            [
                'title' => 'Corporate Governance & Compliance',
                'slug' => 'corporate-governance',
                'content' => '<h2>Corporate Governance</h2><p>TG Microfinance operates under strict regulatory oversight, adhering to ethical lending practices and transparent reporting.</p>',
                'status' => 'published',
            ],
        ];

        foreach ($pages as $pageData) {
            Page::firstOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }

        // 5. Loan Products CMS Initial Data
        $loanProducts = [
            [
                'name' => 'Micro-Enterprise Loan',
                'slug' => 'micro-enterprise-loan',
                'description' => 'Fast working capital for small shop owners, trade vendors, and artisans needing quick inventory funds.',
                'min_amount' => '500',
                'max_amount' => '5,000',
                'interest_rate' => '12.5% P.A.',
                'tenure' => '6 to 18 Months',
                'repayment_frequency' => 'Flexible Weekly/Monthly Repayments',
                'icon' => 'bi-briefcase',
                'badge_color' => 'primary',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'name' => 'Group Solidarity Loan',
                'slug' => 'group-solidarity-loan',
                'description' => 'Community group lending model empowering self-help groups with cross-guaranteed credit facilities.',
                'min_amount' => '200',
                'max_amount' => '2,000 / member',
                'interest_rate' => '11.0% P.A.',
                'tenure' => '12 Months',
                'repayment_frequency' => 'No Individual Collateral Required',
                'icon' => 'bi-people',
                'badge_color' => 'success',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'name' => 'SME Expansion Loan',
                'slug' => 'sme-expansion-loan',
                'description' => 'Substantial credit facility for established small businesses investing in machinery and equipment upgrades.',
                'min_amount' => '5,000',
                'max_amount' => '25,000',
                'interest_rate' => '14.0% P.A.',
                'tenure' => '12 to 36 Months',
                'repayment_frequency' => 'Custom Monthly Repayment Schedule',
                'icon' => 'bi-building',
                'badge_color' => 'info',
                'status' => 'active',
                'sort_order' => 3,
            ],
        ];

        foreach ($loanProducts as $lp) {
            CmsLoanProduct::firstOrCreate(
                ['slug' => $lp['slug']],
                $lp
            );
        }

        // 6. Savings Products CMS Initial Data
        $savingsProducts = [
            [
                'name' => 'Regular Savings Account',
                'slug' => 'regular-savings-account',
                'description' => 'Everyday savings with monthly compound interest credits and zero account maintenance fees.',
                'interest_rate' => '4.5% P.A.',
                'min_balance' => '10',
                'tenure' => 'Flexible Access',
                'features' => ['Passbook Included', 'Monthly Interest Payout', 'Zero Account Fee'],
                'icon' => 'bi-wallet2',
                'badge_color' => 'success',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'name' => 'Fixed Term Deposit',
                'slug' => 'fixed-term-deposit',
                'description' => 'Guaranteed high returns when locking funds for fixed tenure terms.',
                'interest_rate' => 'Up to 8.5% P.A.',
                'min_balance' => '100',
                'tenure' => '3, 6, 12, or 24 Months',
                'features' => ['Guaranteed Maturity Payout', 'High Yield Interest', 'Premature Exit Support'],
                'icon' => 'bi-bank',
                'badge_color' => 'primary',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'name' => 'Group Savings Account',
                'slug' => 'group-savings-account',
                'description' => 'Joint passbook savings tailored for self-help groups and registered micro cooperatives.',
                'interest_rate' => '6.0% P.A.',
                'min_balance' => '50',
                'tenure' => 'Joint Group Term',
                'features' => ['Multi-Signatory Verification', 'Joint Member Ledgers', 'Group Bonus Credit'],
                'icon' => 'bi-people',
                'badge_color' => 'info',
                'status' => 'active',
                'sort_order' => 3,
            ],
        ];

        foreach ($savingsProducts as $sp) {
            CmsSavingsProduct::firstOrCreate(
                ['slug' => $sp['slug']],
                $sp
            );
        }

        // 7. News CMS Initial Data
        $newsItems = [
            [
                'title' => 'TG Microfinance Expands Branch Network to 85 Locations',
                'slug' => 'tg-microfinance-expands-branch-network-to-85-locations',
                'short_description' => 'Our new branch operations deliver accessible micro-credit counters and field collection services to underserved agricultural markets.',
                'content' => '<p>TG Microfinance is pleased to announce the official opening of 10 new regional branch counters across Eastern districts.</p><p>This strategic expansion brings our physical network to 85 operational branches, serving over 50,000 active micro-borrowers and self-help group members with instant cash disbursements and field banking access.</p>',
                'published_date' => '2025-10-14',
                'status' => 'published',
                'sort_order' => 1,
            ],
            [
                'title' => 'Annual Financial Audit Confirms Outstanding Portfolio Health',
                'slug' => 'annual-financial-audit-confirms-outstanding-portfolio-health',
                'short_description' => 'Independent external audit results highlight a 99.2% repayment efficiency rate and robust general ledger controls across all branch vaults.',
                'content' => '<p>The FY 2025 financial audit conducted by independent certified auditors confirms strong financial capitalization and double-entry accounting integrity.</p><p>Key highlights include a 99.2% loan recovery rate, total loan disbursement exceeding $120 Million, and zero vault discrepancy across physical counter operations.</p>',
                'published_date' => '2025-09-28',
                'status' => 'published',
                'sort_order' => 2,
            ],
        ];

        foreach ($newsItems as $newsData) {
            News::firstOrCreate(
                ['slug' => $newsData['slug']],
                $newsData
            );
        }

        // 8. Gallery CMS Initial Data
        $galleryItems = [
            [
                'title' => 'Annual Member Summit 2025',
                'category' => 'Events',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => 'Financial Literacy Workshop for Self-Help Groups',
                'category' => 'Workshops',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'title' => 'New Regional Branch Inauguration Ceremony',
                'category' => 'Branches',
                'status' => 'active',
                'sort_order' => 3,
            ],
        ];

        foreach ($galleryItems as $gData) {
            Gallery::firstOrCreate(
                ['title' => $gData['title']],
                array_merge(['image' => 'cms/gallery/sample.jpg'], $gData)
            );
        }

        // 9. Downloads CMS Initial Data
        $downloadItems = [
            [
                'title' => 'Individual Loan Application Form',
                'description' => 'Printable PDF application kit for micro-enterprise and personal business loans.',
                'file' => 'cms/downloads/individual_loan_application.pdf',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => 'Group Savings Registration Kit',
                'description' => 'Joint member agreement and passbook request form for self-help groups.',
                'file' => 'cms/downloads/group_savings_registration_kit.pdf',
                'status' => 'active',
                'sort_order' => 2,
            ],
        ];

        foreach ($downloadItems as $dData) {
            Download::firstOrCreate(
                ['title' => $dData['title']],
                $dData
            );
        }

        // 10. FAQ CMS Initial Data
        $faqItems = [
            [
                'question' => 'What documents are required to apply for a Micro-Enterprise Loan?',
                'answer' => "You will need a valid government-issued National Identity Card or Passport, proof of business address/stall location, and basic guarantor details.\n\nOur field loan officers can also visit your commercial stall to assist with documentation verification.",
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'question' => 'How fast are micro-loan applications approved?',
                'answer' => 'Standard micro-loans undergo digital KYC and field officer verification within 24 to 48 hours following document submission. Approved funds can be collected at any branch counter or disbursed directly to your mobile wallet.',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'question' => 'Can field collection officers collect deposits directly from my business stall?',
                'answer' => 'Yes. Authorized branch collection officers carry secure mobile ERP devices equipped with portable printers to issue instant electronic receipts for all field repayments and savings deposits.',
                'status' => 'active',
                'sort_order' => 3,
            ],
        ];

        foreach ($faqItems as $fData) {
            Faq::firstOrCreate(
                ['question' => $fData['question']],
                $fData
            );
        }

        // 11. Footer CMS Initial Data
        FooterSetting::firstOrCreate(
            ['id' => 1],
            [
                'about_text' => 'Empowering individuals, micro-entrepreneurs, and small businesses with accessible credit solutions, high-yield savings schemes, and financial literacy.',
                'address' => '100 Financial Avenue, Suite 500',
                'phone' => '+1 (800) 555-0199',
                'email' => 'info@tgmicrofinance.org',
                'copyright_text' => '© ' . date('Y') . ' Astha Welfare Society. Developed By Tech Googly',
                'social_links' => [
                    'facebook' => 'https://facebook.com/tgmicrofinance',
                    'twitter' => 'https://twitter.com/tgmicrofinance',
                    'linkedin' => 'https://linkedin.com/company/tgmicrofinance',
                    'instagram' => 'https://instagram.com/tgmicrofinance',
                    'youtube' => 'https://youtube.com/@tgmicrofinance',
                ],
            ]
        );

        // 12. SEO CMS Initial Data
        $seoPages = [
            [
                'page_name' => 'home',
                'meta_title' => 'TG Microfinance ERP - Empowering Micro-Entrepreneurs',
                'meta_description' => 'Regulated microfinance institution providing fast loans, high-yield savings accounts, and field banking services.',
                'keywords' => 'microfinance, micro-loans, SME loans, savings account, field banking',
                'status' => 'active',
            ],
            [
                'page_name' => 'about',
                'meta_title' => 'About Us - Corporate Profile & Governance',
                'meta_description' => 'Learn about our 15-year mission of financial inclusion, executive board, and audited portfolio health.',
                'keywords' => 'about microfinance, financial inclusion, corporate governance',
                'status' => 'active',
            ],
            [
                'page_name' => 'contact',
                'meta_title' => 'Contact Customer Support - Head Office & Hotline',
                'meta_description' => 'Get in touch with our customer service team, submit branch inquiries, or speak to a loan officer.',
                'keywords' => 'contact microfinance, customer hotline, branch locator',
                'status' => 'active',
            ],
        ];

        foreach ($seoPages as $seo) {
            SeoSetting::firstOrCreate(
                ['page_name' => $seo['page_name']],
                $seo
            );
        }

        // 13. Contact Inquiries CMS Initial Data
        $sampleInquiries = [
            [
                'name' => 'Michael Scott',
                'email' => 'michael@dundermifflin.com',
                'phone' => '+1 (555) 234-5678',
                'subject' => 'SME Expansion Loan Inquiry',
                'message' => 'Hello, I am interested in applying for an SME Expansion Loan for expanding our paper distribution warehouse. Please send details.',
                'status' => 'unread',
            ],
            [
                'name' => 'Pam Beesly',
                'email' => 'pam@artstudio.org',
                'phone' => '+1 (555) 876-5432',
                'subject' => 'Group Savings Passbook Requirements',
                'message' => 'We are forming a local art cooperative and would like to open a joint group savings account. What documentation is required?',
                'status' => 'read',
            ],
        ];

        foreach ($sampleInquiries as $inq) {
            ContactInquiry::firstOrCreate(
                ['email' => $inq['email']],
                $inq
            );
        }

        // 14. Why Choose Us CMS Initial Data
        $whyChooseItems = [
            [
                'title' => 'Bank-Grade Security',
                'description' => 'Encrypted user sessions, role-based access control, and complete audit trail logging.',
                'icon' => 'bi-shield-lock',
                'badge_color' => 'primary',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => 'Fast Loan Approval',
                'description' => 'Streamlined KYC verification allowing rapid decision turnarounds within 24 to 48 hours.',
                'icon' => 'bi-lightning-charge',
                'badge_color' => 'success',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'title' => '100% Transparent Terms',
                'description' => 'Zero hidden fees, transparent interest rate calculations, and clear repayment schedules.',
                'icon' => 'bi-eye',
                'badge_color' => 'info',
                'status' => 'active',
                'sort_order' => 3,
            ],
            [
                'title' => 'Digital Services',
                'description' => 'Instant SMS transaction receipts, mobile collection logging, and online application tracking.',
                'icon' => 'bi-phone',
                'badge_color' => 'warning',
                'status' => 'active',
                'sort_order' => 4,
            ],
            [
                'title' => 'Trusted Community Partner',
                'description' => 'Serving over 50,000 active micro-borrowers and self-help group members with high satisfaction.',
                'icon' => 'bi-heart',
                'badge_color' => 'danger',
                'status' => 'active',
                'sort_order' => 5,
            ],
            [
                'title' => 'Extensive Branch Network',
                'description' => '85+ nationwide branch offices providing physical cash counters and loan officer support.',
                'icon' => 'bi-diagram-3',
                'badge_color' => 'primary',
                'status' => 'active',
                'sort_order' => 6,
            ],
        ];

        foreach ($whyChooseItems as $wcItem) {
            \App\Models\WhyChooseUs::firstOrCreate(
                ['title' => $wcItem['title']],
                $wcItem
            );
        }

        // 15. Team Members CMS Initial Data
        $teamMembers = [
            [
                'name' => 'Dr. Arthur Pendelton',
                'designation' => 'Chairman & Non-Executive Director',
                'type' => 'board',
                'bio' => 'Over 25 years of experience in central banking regulation, financial inclusion policy, and microfinance governance.',
                'display_order' => 1,
                'status' => 'active',
            ],
            [
                'name' => 'Eleanor Vance',
                'designation' => 'Chief Executive Officer (CEO)',
                'type' => 'management',
                'bio' => 'Pioneered digital collection systems and expanded branch network serving over 50,000 active micro-borrowers.',
                'display_order' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Marcus Sterling',
                'designation' => 'Head of Credit & Risk Assessment',
                'type' => 'management',
                'bio' => 'Oversees credit appraisal, non-performing asset prevention, and field officer audit procedures.',
                'display_order' => 3,
                'status' => 'active',
            ],
        ];

        foreach ($teamMembers as $tm) {
            \App\Models\TeamMember::firstOrCreate(
                ['name' => $tm['name']],
                $tm
            );
        }

        // 16. Interest Rates CMS Initial Data
        $interestRateEntries = [
            [
                'product_name' => 'Micro-Enterprise Loan',
                'product_type' => 'loan',
                'amount_range' => '$500 – $5,000',
                'tenure_options' => '6 – 18 Months',
                'interest_rate' => '12.5% P.A.',
                'interest_method' => 'Reducing Balance',
                'processing_fee' => '1.0%',
                'description' => 'Calculated on monthly reducing balance method.',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'product_name' => 'Group Solidarity Loan',
                'product_type' => 'loan',
                'amount_range' => '$200 – $2,000',
                'tenure_options' => '12 Months',
                'interest_rate' => '11.0% P.A.',
                'interest_method' => 'Reducing Balance',
                'processing_fee' => '0.5%',
                'description' => 'No individual collateral required. Group guarantee model.',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'product_name' => 'SME Expansion Loan',
                'product_type' => 'loan',
                'amount_range' => '$5,000 – $25,000',
                'tenure_options' => '12 – 36 Months',
                'interest_rate' => '14.0% P.A.',
                'interest_method' => 'Flat',
                'processing_fee' => '1.5%',
                'description' => 'Working capital expansion loan for growing businesses.',
                'status' => 'active',
                'sort_order' => 3,
            ],
        ];

        foreach ($interestRateEntries as $ir) {
            \App\Models\InterestRate::firstOrCreate(
                ['product_name' => $ir['product_name']],
                $ir
            );
        }

        // 17. Services CMS Initial Data
        $servicesData = [
            [
                'title' => 'Digital Banking',
                'slug' => 'digital-banking',
                'icon' => 'bi-phone',
                'short_description' => 'Instant SMS transaction alerts, mobile wallet integration, and online account status tracking.',
                'content' => '<p>Our Digital Banking suite empowers members with instant SMS transaction receipts, mobile collection logging, and digital account balance tracking right from any mobile device.</p>',
                'meta_title' => 'Digital Banking & Mobile Wallet Services | TG Microfinance',
                'meta_description' => 'Instant digital receipts, SMS collection alerts, and online balance inquiries for microfinance members.',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => 'Collection Services',
                'slug' => 'collection-services',
                'icon' => 'bi-journal-check',
                'short_description' => 'Doorstep collection officers equipped with mobile ERP handheld devices for instant receipting.',
                'content' => '<p>Field collection officers visit your commercial stall or group meeting point, processing daily/weekly loan repayments and savings deposits with instant thermal paper receipts.</p>',
                'meta_title' => 'Doorstep Field Collection Services | TG Microfinance',
                'meta_description' => 'Authorized field collection officers providing instant electronic receipts at your business stall.',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'title' => 'Financial Advisory',
                'slug' => 'financial-advisory',
                'icon' => 'bi-graph-up-arrow',
                'short_description' => 'Financial literacy workshops, business budgeting guidance, and self-help group mentoring.',
                'content' => '<p>We conduct regular financial literacy workshops and offer complimentary business counseling to help micro-entrepreneurs manage cash flows effectively.</p>',
                'meta_title' => 'Financial Advisory & Business Coaching | TG Microfinance',
                'meta_description' => 'Financial literacy workshops and business cash flow management for small enterprise owners.',
                'status' => 'active',
                'sort_order' => 3,
            ],
        ];

        foreach ($servicesData as $serv) {
            \App\Models\CmsService::firstOrCreate(
                ['slug' => $serv['slug']],
                $serv
            );
        }

        // 18. Careers CMS Initial Data
        $careersData = [
            [
                'title' => 'Loan Officer',
                'slug' => 'loan-officer',
                'location' => 'Multiple Branch Locations',
                'job_type' => 'Full-Time',
                'short_description' => 'Responsible for customer onboarding, loan application appraisal, and monitoring portfolio repayments.',
                'requirements' => 'Diploma or Bachelor degree in Finance/Business administration. 1+ year field credit experience preferred.',
                'application_email' => 'hr@tgmicrofinance.org',
                'deadline' => '2026-12-31',
                'apply_button_text' => 'Apply for Position',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => 'Collection Officer',
                'slug' => 'collection-officer',
                'location' => 'Field Route Operations',
                'job_type' => 'Full-Time',
                'short_description' => 'Conducting doorstep collection visits, issuing mobile ERP receipts, and maintaining daily route logs.',
                'requirements' => 'High school diploma. Valid motorcycle driving license and strong field communication skills.',
                'application_email' => 'hr@tgmicrofinance.org',
                'deadline' => '2026-12-31',
                'apply_button_text' => 'Apply for Position',
                'status' => 'active',
                'sort_order' => 2,
            ],
        ];

        foreach ($careersData as $car) {
            \App\Models\Career::firstOrCreate(
                ['slug' => $car['slug']],
                $car
            );
        }
    }
}
