<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Product;
use App\Models\TradeAccount;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Delivery;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * The overview leads with one number: requests still New after their response target
 * (2 hours for order requests). Everything else on the page is context for it.
 */
class DashboardController extends Controller
{
    private const int CHART_DAYS = 30;

    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $mine = fn () => Lead::visibleTo($user);
        $chartStart = today()->subDays(self::CHART_DAYS - 1);

        $overdue = $mine()->overdue();
        $oldest = (clone $overdue)->min('created_at');
        $overdueByType = (clone $overdue)->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        $dates = $mine()->where('created_at', '>=', $chartStart)->pluck('created_at')->countBy(fn (Carbon $d) => $d->toDateString());
        $daily = collect(range(0, self::CHART_DAYS - 1))->map(function (int $i) use ($chartStart, $dates) {
            $day = $chartStart->copy()->addDays($i);

            return ['date' => $day, 'count' => (int) ($dates[$day->toDateString()] ?? 0)];
        });

        // Today and the next three working days (weekends and closed dates skipped).
        $deliveryUntil = (Delivery::days()[2] ?? today()->addDays(4))->copy();
        $tiles = [];
        if ($user->canWork(LeadType::Order)) {
            $orders = fn () => $mine()->where('type', LeadType::Order);
            // Median minutes from arrival to first contact over the last 30 days.
            $responses = $orders()->whereNotNull('first_contacted_at')->where('created_at', '>=', now()->subDays(30))->get(['created_at', 'first_contacted_at'])
                ->map(fn (Lead $l) => (int) $l->created_at->diffInMinutes($l->first_contacted_at))->sort()->values();
            $tiles[] = ['label' => 'Order requests, last 7 days', 'value' => $orders()->where('created_at', '>=', now()->subDays(7))->count(),
                'previous' => $orders()->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count(), 'period' => 'vs previous 7 days',
                'href' => route('admin.leads.index', ['type' => 'order'])];
            $tiles[] = ['label' => 'Median time to confirm an order', 'value' => $responses->isEmpty() ? null : (int) $responses->median(), 'format' => 'minutes',
                'note' => 'last 30 days, '.$responses->count().' orders', 'href' => route('admin.leads.index', ['type' => 'order'])];
            $tiles[] = ['label' => 'Deliveries requested, next 3 working days', 'value' => $orders()->whereNotIn('status', [LeadStatus::Cancelled, LeadStatus::Delivered])
                ->whereBetween('delivery_date', [today(), $deliveryUntil])->count(), 'note' => 'confirm the day with each customer', 'href' => route('admin.leads.index', ['type' => 'order', 'status' => 'open'])];
        }
        if ($user->canWork(LeadType::Account)) {
            $tiles[] = ['label' => 'Trade account applications open', 'value' => $mine()->where('type', LeadType::Account)->open()->count(),
                'note' => $mine()->where('type', LeadType::Account)->where('status', LeadStatus::NeedsDocuments)->count().' waiting on documents',
                'href' => $user->hasRole(Role::Admin, Role::Credit) ? route('admin.credit.index') : route('admin.leads.index', ['type' => 'account'])];
            $tiles[] = ['label' => 'Active trade accounts', 'value' => TradeAccount::where('status', AccountStatus::Active)->count(),
                'note' => TradeAccount::where('status', AccountStatus::OnHold)->count().' on hold', 'href' => route('admin.accounts.index')];
        }
        if ($user->canWork(LeadType::Application)) {
            $tiles[] = ['label' => 'Job applications, last 30 days', 'value' => $mine()->where('type', LeadType::Application)->where('created_at', '>=', now()->subDays(30))->count(),
                'note' => $mine()->where('type', LeadType::Application)->where('status', LeadStatus::Shortlisted)->count().' shortlisted', 'href' => route('admin.leads.index', ['type' => 'application'])];
            $tiles[] = ['label' => 'Open vacancies', 'value' => Vacancy::open()->count(), 'note' => 'on the careers page', 'href' => route('admin.vacancies.index')];
        }
        if ($user->hasRole(Role::Admin, Role::Sales)) {
            $tiles[] = ['label' => 'Products in the catalogue', 'value' => Product::listed()->count(), 'note' => 'published, no prices', 'href' => route('admin.products.index')];
        }

        $types = $user->role->leadTypes();

        return view('admin.dashboard', [
            'overdueCount' => $overdue->count(),
            'overdueByType' => collect($types)->map(fn (LeadType $t) => ['type' => $t, 'count' => (int) ($overdueByType[$t->value] ?? 0)])->filter(fn ($r) => $r['count'] > 0)->values(),
            'oldestOverdue' => $oldest ? Carbon::parse($oldest) : null,
            'newCount' => $mine()->where('status', LeadStatus::New)->count(),
            'types' => $types,
            'tiles' => array_slice($tiles, 0, 4),
            'daily' => $daily,
            'dailyMax' => max(1, (int) $daily->max('count')),
            'chartDays' => self::CHART_DAYS,
            'byType' => collect($types)->map(fn (LeadType $t) => [
                'type' => $t, 'count' => $mine()->where('type', $t)->where('created_at', '>=', $chartStart)->count(),
            ])->sortByDesc('count')->values(),
            'queue' => $mine()->with(['vacancy', 'tradeAccount'])->where('status', LeadStatus::New)->oldest()->limit(8)->get(),
            'deliveries' => $user->canWork(LeadType::Order) ? $mine()->where('type', LeadType::Order)->whereNotIn('status', [LeadStatus::Cancelled, LeadStatus::Delivered])
                ->whereBetween('delivery_date', [today(), $deliveryUntil])->orderBy('delivery_date')->limit(8)->get() : collect(),
            'notifyMissing' => $user->isAdmin() && Notify::recipients() === [],
            'notifyInvalid' => $user->isAdmin() ? Notify::invalid() : [],
        ]);
    }
}
