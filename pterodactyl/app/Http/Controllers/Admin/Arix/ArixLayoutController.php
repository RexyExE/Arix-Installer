<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixLayoutRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixLayoutController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.arix.layout', [
            'navbar' => 'layout',
            'sideEditor' => true,
            'layout' => $this->settings->get('settings::arix:layout', 1),
            'searchComponent' => $this->settings->get('settings::arix:searchComponent', 1),
            'logoPosition' => $this->settings->get('settings::arix:logoPosition', 1),
            'socialPosition' => $this->settings->get('settings::arix:socialPosition', 1),
            'loginLayout' => $this->settings->get('settings::arix:loginLayout', 1),
        ]);
    }

    public function store(ArixLayoutRequest $request)
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix.layout');
    }
}