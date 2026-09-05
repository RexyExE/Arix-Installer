<?php

namespace Pterodactyl\Http\Controllers\Admin\Arix;

use Illuminate\View\View;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Admin\Arix\ArixMetaRequest;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class ArixMetaController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private SettingsRepositoryInterface $settings,
        private ViewFactory $view
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.arix.meta', [
            'navbar' => 'meta',
            'sideEditor' => false,
            'meta_color' => $this->settings->get('settings::arix:meta_color', '#4a35cf'),
            'meta_title' => $this->settings->get('settings::arix:meta_title', 'Pterodactyl Panel'),
            'meta_description' => $this->settings->get('settings::arix:meta_description', 'Our official Pterodactyl panel'),
            'meta_image' => $this->settings->get('settings::arix:meta_image', '/arix/meta-tags.png'),
            'meta_favicon' => $this->settings->get('settings::arix:meta_favicon', '/arix/Arix.png'),
        ]);
    }

    public function store(ArixMetaRequest $request)
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }
        $this->alert->success('Theme settings have been updated successfully.')->flash();
        return redirect()->route('admin.arix.meta');
    }
}