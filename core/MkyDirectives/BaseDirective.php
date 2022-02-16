<?php


namespace MkyCore\MkyDirectives;

use MkyCore\Facades\Route;
use MkyCore\Security\CsrfMiddleware;
use MkyEngine\Interfaces\MkyDirectiveInterface;

class BaseDirective implements MkyDirectiveInterface
{

    public function getFunctions()
    {
        return [
            'asset' => [$this, 'asset'],
            'dump' => [$this, 'dump'],
            'can' => [[$this, 'can'], [$this, 'endcan']],
            'notcan' => [[$this, 'notcan'], [$this, 'endnotcan']],
            'auth' => [[$this, 'auth'], [$this, 'endauth']],
            'guest' => [[$this, 'guest'], [$this, 'endguest']],
            'currentRoute' => [[$this, 'currentRoute'], [$this, 'endcurrentRoute']],
            'route' => [$this, 'route'],
            'csrf' => [$this, 'csrfInput']
        ];
    }


    public function dump($var)
    {
        return "<?php dump($var) ?>";
    }

    public function can($permission, $subject)
    {
        $condition = json_encode(\MkyCore\Facades\Permission::authorizeAuth($permission, $subject));
        return "<?php if($condition): ?>";
    }

    public function endcan()
    {
        return '<?php endif; ?>';
    }

    public function notcan($permission, $subject)
    {
        $condition = json_encode(\MkyCore\Facades\Permission::authorizeAuth($permission, $subject));
        return "<?php if(!$condition): ?>";
    }

    public function endnotcan()
    {
        return '<?php endif; ?>';
    }

    public function auth()
    {
        $cond = json_encode((new \MkyCore\AuthManager())->isLogin());
        return "<?php if($cond): ?>";
    }

    public function endauth()
    {
        return '<?php endif; ?>';
    }

    public function guest()
    {
        $cond = json_encode(!(new \MkyCore\AuthManager())->isLogin());
        return "<?php if($cond): ?>";
    }

    public function endguest()
    {
        return '<?php endif; ?>';
    }

    public function currentRoute(string $name = '', bool $path = false)
    {
        $current = \MkyCore\Facades\Route::currentRoute($name, $path);
        if($name){
            $current = json_encode($current);
            return "<?php if($current): ?>";
        }
        return $current;
    }

    public function endcurrentRoute()
    {
        return '<?php endif; ?>';
    }

    public function asset(string $path)
    {
        $path = trim($path, '\'\"');
        return BASE_ULR . 'assets/' . $path;
    }

    public function route(string $name, array $params = [])
    {
        return \MkyCore\Facades\Route::generateUrlByName($name, $params);
    }

    public function csrfInput()
    {
        $token = (new CsrfMiddleware())->generateToken();
        return "<input type='hidden' name='_csrf' value='$token'/>";
    }
}