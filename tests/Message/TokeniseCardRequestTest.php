<?php

namespace Omnipay\Braintree\Message;

use Omnipay\Common\CreditCard;
use Omnipay\Tests\TestCase;

class TokeniseCardRequestTest extends TestCase
{
    /**
     * @var TokeniseCardRequest
     */
    private $request;

    public function setUp()
    {
        parent::setUp();

        $this->request = new TokeniseCardRequest($this->getHttpClient(), $this->getHttpRequest());
    }


    public function testGetEndpoint()
    {
        $this->request->setTestMode(true);
        $this->assertEquals('https://payments.sandbox.braintree-api.com/graphql', $this->request->getEndpoint());

        $this->request->setTestMode(false);
        $this->assertEquals('https://payments.braintree-api.com/graphql', $this->request->getEndpoint());
    }

    public function testGetData()
    {
        $card = new CreditCard();
        $card->setNumber('4111111111111111');
        $card->setExpiryMonth('12');
        $card->setExpiryYear('2023');
        $card->setCvv('123');

        $this->request->setCard($card);

        $data = $this->request->getData();

        $expectedGraphQlQuery = <<<GRAPHQL
mutation TokenizeCreditCard(\$input: TokenizeCreditCardInput!) {
	tokenizeCreditCard(input: \$input) {
		paymentMethod {
			id
		}
	}
}
GRAPHQL;

        $expectedData = [
            'query' => $expectedGraphQlQuery,
            'variables' => [
                'input' => [
                    'creditCard' => [
                        'number' => '4111111111111111',
                        'expirationMonth' => '12',
                        'expirationYear' => '2023',
                        'cvv' => '123',
                    ],
                    'options' => [
                        'validate' => false,
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedData, $data);
    }

}
