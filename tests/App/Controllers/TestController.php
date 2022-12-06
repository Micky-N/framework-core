<?php

namespace MkyCore\Tests\App\Controllers;

use MkyCore\Abstracts\Controller;
use MkyCore\Tests\App\Services\PaymentServiceInterface;

class TestController extends Controller
{
    /**
     * @Router('test', methods: ['GET'])
     * @return string
     */
    public function index()
    {
        return 'green';
    }

    public function show($id){
        return 'red '.$id;
    }

    public function post(){
        return $this->request->post('name');
    }

    public function multiple(int $id, string $fa): array
    {
        return [$id, $fa];
    }

    public function test(PaymentServiceInterface $paymentService, int $id)
    {
        return $paymentService->getTotal().'€ pour '.$id;
    }

    /**
     * @return array
     */
    public function optional()
    {
        return $this->request->parameters();
    }
}
