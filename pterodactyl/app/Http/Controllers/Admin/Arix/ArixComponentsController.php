<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixComponentsRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixComponentsController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.arix.components', [
            'navbar' => 'components',
            'sideEditor' => true,
            'serverRow' => $this->settings->get('settings::arix:serverRow', 1),
            'statsCards' => $this->settings->get('settings::arix:statsCards', 2),
            'sideGraphs' => $this->settings->get('settings::arix:sideGraphs', 2),
            'graphs' => $this->settings->get('settings::arix:graphs', 2),
        ]);
    }

    public function store(ArixComponentsRequest $request)
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix.components');
    }
}