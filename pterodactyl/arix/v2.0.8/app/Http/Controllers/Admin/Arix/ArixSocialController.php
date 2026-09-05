<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixSocialRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixSocialController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.arix.social', [
            'navbar' => 'social',
            'sideEditor' => false,
            'socials' => json_decode($this->settings->get('settings::arix:socials', '[]'), true),
            'socialButtons' => $this->settings->get('settings::arix:socialButtons', false),
            'discordBox' => $this->settings->get('settings::arix:discordBox', true),
        ]);
    }

    public function store(ArixSocialRequest $request)
    {
        foreach ($request->normalize() as $key => $value) {
            if (is_array($value)) {
                $value = array_filter($value, fn($item) => $item !== '' && $item !== null);
                $value = json_encode(array_values($value));
            } else {
                if ($value === '' || $value === null) {
                    continue;
                }
            }
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix.social');
    }
}