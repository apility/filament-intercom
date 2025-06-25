<?php

namespace Apility\FilamentIntercom;

use Apility\FilamentIntercom\Contracts\IntercomUser;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class FilamentIntercomPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-intercom';
    }

    public function register(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => Blade::render(<<<HTML
                @auth
                    <script>
                        window.intercomSettings = @json(\$intercomSettings);
                    </script>
                    <script>
                        // We pre-filled your app ID in the widget URL: 'https://widget.intercom.io/widget/{{ \$intercomSettings['app_id'] }}'
                        (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/{{ \$intercomSettings['app_id'] }}';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
                    </script>
                @endauth
            HTML, [
                'intercomSettings' => $this->getIntercomSettings(),
            ])
        );
    }

    protected function getIntercomSettings(): array
    {
        $settings = [
            'api_base' => config('services.intercom.api_base', 'https://api-iam.intercom.io'),
            'app_id' => config('services.intercom.app_id'),
        ];

        if ($user = auth()->user()) {
            $userData = [
                'user_id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->timestamp,
            ];

            if ($user instanceof IntercomUser) {
                $userData = array_merge($userData, $user->getIntercomUserData());
            }

            return array_merge($settings, $userData);
        }

        return $settings;
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): FilamentIntercomPlugin
    {
        return app(static::class);
    }
}
