<?php

namespace Shopper\Framework\Http\Livewire\Settings;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Shopper\Framework\Models\System\Setting;
use WireUi\Traits\Actions;

class Analytics extends Component
{
    use Actions, WithFileUploads;

    public $google_analytics_tracking_id;
    public $google_analytics_view_id;
    public $google_analytics_add_js;
    public $google_tag_manager_account_id;
    public $facebook_pixel_account_id;
    public $json_file;
    public bool $credentials_json = false;

    public function mount()
    {
        $ga_add_js = Setting::query()->where('key', 'google_analytics_add_js')->first();
        $this->google_analytics_tracking_id = env('ANALYTICS_TRACKING_ID');
        $this->google_analytics_view_id = env('ANALYTICS_VIEW_ID');
        $this->google_analytics_add_js = $ga_add_js->value ?? null;
        $this->google_tag_manager_account_id = env('GOOGLE_TAG_MANAGER_ACCOUNT_ID');
        $this->facebook_pixel_account_id = env('FACEBOOK_PIXEL_ACCOUNT_ID');
        $this->credentials_json = File::exists(storage_path('app/analytics/service-account-credentials.json'));
    }

    public function store()
    {
        Artisan::call('config:clear');

        setEnvironmentValue([
            'analytics_tracking_id' => $this->google_analytics_tracking_id,
            'analytics_view_id' => $this->google_analytics_view_id,
            'google_tag_manager_account_id' => $this->google_tag_manager_account_id,
            'facebook_pixel_account_id' => $this->facebook_pixel_account_id,
        ]);

        Setting::query()->updateOrCreate(['key' => 'google_analytics_add_js'], [
            'key' => 'google_analytics_add_js',
            'value' => $this->google_analytics_add_js,
            'locked' => true,
            'display_name' => Setting::lockedAttributesDisplayName('google_analytics_add_js'),
        ]);

        $this->json_file?->storeAs('analytics', 'service-account-credentials.json');

        if (app()->environment('production')) {
            Artisan::call('config:cache');
        }

        $this->notification()->success(__('Updated'), __('Your analytics configurations have been correctly updated.'));
    }

    public function downloadJson(): string
    {
        return Storage::url('/app/analytics/service-account-credentials.json');
    }

    public function render()
    {
        return view('shopper::livewire.settings.analytics');
    }
}
