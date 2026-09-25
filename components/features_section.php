<?php
/**
 * Features & How-It-Works Section Component
 * Two-sided value cards and 4-step milestone walkthrough with pure Tailwind CSS
 */
?>
<!-- Dual-Sided Platform Value -->
<section class="py-20 bg-slate-50 dark:bg-slate-950 border-b border-slate-200/80 dark:border-slate-800" id="features">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14" data-aos="fade-up">
            <div class="inline-flex items-center space-x-2 text-purple-600 dark:text-purple-400 font-semibold text-xs uppercase tracking-wider mb-2">
                <i class="ri-shield-star-line"></i> Dual-Sided Marketplace
            </div>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Engineered for Success on Both Sides</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Whether you're hiring top talent or delivering creative services, FreelanceHub provides the infrastructure to build with confidence.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8" data-aos="fade-up" data-aos-delay="100">
            <!-- Card 1: For Hiring Clients -->
            <div class="bg-gradient-to-br from-purple-50/70 to-indigo-50/40 dark:from-slate-900 dark:to-indigo-950/40 rounded-3xl p-8 border border-purple-100/80 dark:border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-2xl mb-6 shadow-md shadow-purple-200 dark:shadow-none">
                        <i class="ri-user-star-line"></i>
                    </div>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mb-3">For Businesses & Clients</h3>
                    <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-6">Scale your team with elite specialists on demand, backed by escrow milestone protection.</p>

                    <ul class="space-y-3.5 text-sm text-slate-700 dark:text-slate-300 mb-8">
                        <li class="flex items-start gap-3">
                            <i class="ri-checkbox-circle-fill text-purple-600 dark:text-purple-400 text-lg flex-shrink-0 mt-0.5"></i>
                            <span><strong>Escrow Protection:</strong> Release funds only when deliverables meet your satisfaction.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="ri-checkbox-circle-fill text-purple-600 dark:text-purple-400 text-lg flex-shrink-0 mt-0.5"></i>
                            <span><strong>Custom Direct Proposals:</strong> Send specific budget and timeline requests directly to talent.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="ri-checkbox-circle-fill text-purple-600 dark:text-purple-400 text-lg flex-shrink-0 mt-0.5"></i>
                            <span><strong>Verified Reviews:</strong> Only clients with completed orders can submit ratings.</span>
                        </li>
                    </ul>
                </div>

                <a href="./public/gig.php" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-md shadow-purple-200 dark:shadow-none transition-all">
                    <span>Browse Marketplace Talent</span>
                    <i class="ri-arrow-right-line"></i>
                </a>
            </div>

            <!-- Card 2: For Skilled Freelancers -->
            <div class="bg-gradient-to-br from-blue-50/70 to-cyan-50/40 dark:from-slate-900 dark:to-cyan-950/30 rounded-3xl p-8 border border-blue-100/80 dark:border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-2xl mb-6 shadow-md shadow-blue-200 dark:shadow-none">
                        <i class="ri-code-box-line"></i>
                    </div>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white mb-3">For Freelancers & Creators</h3>
                    <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-6">Monetize your skills globally with zero upfront fees, automated wallet payouts, and direct client contracts.</p>

                    <ul class="space-y-3.5 text-sm text-slate-700 dark:text-slate-300 mb-8">
                        <li class="flex items-start gap-3">
                            <i class="ri-checkbox-circle-fill text-blue-600 dark:text-blue-400 text-lg flex-shrink-0 mt-0.5"></i>
                            <span><strong>Instant Wallet Credit:</strong> Earnings are credited directly to your balance upon order completion.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="ri-checkbox-circle-fill text-blue-600 dark:text-blue-400 text-lg flex-shrink-0 mt-0.5"></i>
                            <span><strong>Unlimited Gig Publishing:</strong> Showcase multiple tiers of services to international clients.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="ri-checkbox-circle-fill text-blue-600 dark:text-blue-400 text-lg flex-shrink-0 mt-0.5"></i>
                            <span><strong>Zero Hidden Fees:</strong> Complete transparency on project milestones and earnings.</span>
                        </li>
                    </ul>
                </div>

                <a href="./auth/signup.php" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-200 dark:shadow-none transition-all">
                    <span>Apply as a Freelancer</span>
                    <i class="ri-arrow-right-line"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Milestones -->
<section class="py-20 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800" id="how-it-works">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16" data-aos="fade-up">
            <div class="inline-flex items-center space-x-2 text-purple-600 dark:text-purple-400 font-semibold text-xs uppercase tracking-wider mb-2">
                <i class="ri-lightbulb-line"></i> Effortless Collaboration
            </div>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">How FreelanceHub Works</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Get from initial idea to completed deliverable in four streamlined milestones.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Step 1 -->
            <div class="bg-slate-50 dark:bg-slate-800/80 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-700/80 shadow-xs relative" data-aos="fade-up">
                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white font-extrabold text-sm flex items-center justify-center mb-5 shadow-sm">
                    01
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Discover & Connect</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Search through catalogued marketplace gigs or submit a custom direct project proposal.</p>
            </div>

            <!-- Step 2 -->
            <div class="bg-slate-50 dark:bg-slate-800/80 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-700/80 shadow-xs relative" data-aos="fade-up" data-aos-delay="100">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white font-extrabold text-sm flex items-center justify-center mb-5 shadow-sm">
                    02
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Escrow Commitment</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Funds are securely reserved in escrow, ensuring talent starts working with verified guarantees.</p>
            </div>

            <!-- Step 3 -->
            <div class="bg-slate-50 dark:bg-slate-800/80 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-700/80 shadow-xs relative" data-aos="fade-up" data-aos-delay="200">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-extrabold text-sm flex items-center justify-center mb-5 shadow-sm">
                    03
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Review Deliverables</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Collaborate directly, request revisions, and verify project milestones through the dashboard.</p>
            </div>

            <!-- Step 4 -->
            <div class="bg-slate-50 dark:bg-slate-800/80 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-700/80 shadow-xs relative" data-aos="fade-up" data-aos-delay="300">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white font-extrabold text-sm flex items-center justify-center mb-5 shadow-sm">
                    04
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Approve & Payout</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Approve completed work, credit wallet balance to the freelancer, and leave verified feedback.</p>
            </div>
        </div>
    </div>
</section>
