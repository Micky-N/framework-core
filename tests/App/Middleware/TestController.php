<?php

namespace MkyCore\Tests\App\Middleware;

use MkyCore\Abstracts\Controller;
use MkyCore\Tests\App\Services\PaymentServiceInterface;

class TestController extends Controller
{

    /**
     * @Router('/', middlewares:['])
     * @return string
     */
    public function index(){
        return 'green';
    }

    public function show($id){
        return 'red '.$id;
    }

    public function post(array $data){
        return $data['name'];
    }

    public function multiple($id, $fa)
    {
        return [$id, $fa];
    }

    public function test(PaymentServiceInterface $paymentService, int $id)
    {
        return $paymentService->getTotal().'€ pour '.$id;
    }
}
