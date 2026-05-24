<?php

namespace App\Providers;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\Submission;
use App\Models\User;
use App\Policies\ExamAssignmentPolicy;
use App\Policies\ExamPolicy;
use App\Policies\SubmissionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Exam::class, ExamPolicy::class);
        Gate::policy(ExamAssignment::class, ExamAssignmentPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);

        Paginator::useTailwind();

        if (env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }
    }
}
