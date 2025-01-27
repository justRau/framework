<?php

declare(strict_types=1);

/**
 * Contains the PaymentMethodTest class.
 *
 * @copyright   Copyright (c) 2020 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2020-04-26
 *
 */

namespace Vanilo\Payment\Tests;

use Vanilo\Payment\Contracts\PaymentMethod as PaymentMethodContract;
use Vanilo\Payment\Models\PaymentMethod;
use Vanilo\Payment\PaymentGateways;
use Vanilo\Payment\Tests\Examples\PlasticPayments;

class PaymentMethodTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $method = PaymentMethod::create([
            'name' => 'Credit Card',
            'gateway' => 'plastic'
        ]);

        $this->assertInstanceOf(PaymentMethod::class, $method);
        $this->assertEquals('Credit Card', $method->name);
        $this->assertEquals('plastic', $method->gateway);
    }

    /** @test */
    public function it_has_a_default_timeout()
    {
        $method = PaymentMethod::create([
            'name' => 'Credit Card',
            'gateway' => 'plastic'
        ]);

        $this->assertIsInt($method->getTimeout());
        $this->assertEquals(PaymentMethodContract::DEFAULT_TIMEOUT, $method->getTimeout());
    }

    /** @test */
    public function the_configuration_field_is_an_array()
    {
        $method = PaymentMethod::create([
            'name' => 'Credit Card',
            'gateway' => 'plastic'
        ]);

        $this->assertIsArray($method->configuration);
    }

    /** @test */
    public function default_configuration_is_an_empty_array()
    {
        $method = PaymentMethod::create([
            'name' => 'Credit Card',
            'gateway' => 'plastic'
        ]);

        $this->assertIsArray($method->getConfiguration());
        $this->assertEmpty($method->getConfiguration());
    }

    /** @test */
    public function configuration_can_be_set_as_array()
    {
        $method = PaymentMethod::create([
            'name' => 'Credit Card',
            'gateway' => 'plastic',
            'configuration' => ["asd" => "qwe"],
        ]);

        $method = $method->fresh();
        $this->assertIsArray($method->getConfiguration());
        $this->assertEquals('qwe', $method->configuration['asd']);
    }

    /** @test */
    public function methods_can_be_enabled()
    {
        $method = PaymentMethod::create([
            'name' => 'Credit Card',
            'gateway' => 'plastic',
            'is_enabled' => true,
        ]);

        $this->assertTrue($method->isEnabled());
        $this->assertTrue($method->is_enabled);
    }

    /** @test */
    public function it_can_make_its_gateway()
    {
        PaymentGateways::register('plastic', PlasticPayments::class);
        $method = PaymentMethod::create([
            'name' => 'Credit Card',
            'gateway' => 'plastic'
        ]);

        $gateway = $method->getGateway();
        $this->assertInstanceOf(PlasticPayments::class, $gateway);
        $this->assertEquals(PlasticPayments::getName(), $gateway::getName());
    }
}
