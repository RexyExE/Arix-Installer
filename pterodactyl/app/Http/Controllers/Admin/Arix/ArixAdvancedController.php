<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixAdvancedRequest;
use Pterodactyl\Http\Requests\Admin\Arix\ArixPresetRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Models\Setting;
use Pterodactyl\Traits\Helpers\AvailableLanguages;

class ArixAdvancedController extends Controller
{
    use AvailableLanguages;

    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        $arixSettings = Setting::query()
            ->where('key', 'like', 'settings::arix:%')
            ->get()
            ->mapWithKeys(function (Setting $setting) {
                $shortKey = preg_replace('/^settings::arix:/', '', $setting->key);
                return [$shortKey => $setting->value];
            })
            ->toArray();

        return $this->view->make('admin.arix.advanced', [
            'navbar' => 'advanced',
            'sideEditor' => false,
            'profileType' => $this->settings->get('settings::arix:profileType', 'gravatar'),
            'modeToggler' => $this->settings->get('settings::arix:modeToggler', true),
            'langSwitch' => $this->settings->get('settings::arix:langSwitch', true),
            'defaultLang' => $this->settings->get('settings::arix:defaultLang', 'en'),
            'languageOptions' => $this->settings->get('settings::arix:languageOptions', '[{"key":"en","name":"English"}]'),
            'ipFlag' => $this->settings->get('settings::arix:ipFlag', true),
            'lowResourcesAlert' => $this->settings->get('settings::arix:lowResourcesAlert', false),
            'alertLink' => $this->settings->get('settings::arix:alertLink', ''),
            'dashboardPage' => $this->settings->get('settings::arix:dashboardPage', true),
            'registration' => $this->settings->get('settings::arix:registration', false),
            'defaultMode' => $this->settings->get('settings::arix:defaultMode', 'darkmode'),
            'copyright' => $this->settings->get('settings::arix:copyright', 'Designed by Weijers.one'),
            'arixSettings' => $arixSettings,
            'languages' => $this->getAvailableLanguages(false),
        ]);
    }

    public function preset(ArixPresetRequest $request)
    {
        $validated = $request->validated();
        unset($validated['preset_json']);
        foreach ($validated as $key => $value) {
            $this->settings->set("settings::arix:{$key}", $value);
        }
        $this->alert->success('Preset imported and applied successfully.')->flash();
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'applied' => $validated]);
        }
        return redirect()->route('admin.arix.advanced');
    }

    public function store(ArixAdvancedRequest $request)
    {
        $data = $request->validated();
        if (isset($data['arix:languageOptions']) && is_array($data['arix:languageOptions'])) {
            $languages = $this->getAvailableLanguages();
            $languageOptions = [];
            foreach ($data['arix:languageOptions'] as $key) {
                if (is_string($key) && isset($languages[$key])) {
                    $languageOptions[] = ['key' => $key, 'name' => $languages[$key]];
                }
            }
            $data['arix:languageOptions'] = json_encode($languageOptions);
        }
        foreach ($data as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix.advanced');
    }
}