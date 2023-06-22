<?php
namespace Omnipay\Braintree\Message;

use Braintree\Util;
use Omnipay\Common\Message\AbstractRequest;

/**
 * Authorize Request.
 *
 * @method TokeniseCardRequest send()
 */
class TokeniseCardRequest extends AbstractRequest
{
    public function getClientToken()
    {
        return $this->getParameter('clientToken');
    }

    public function setClientToken($value)
    {
        return $this->setParameter('clientToken', $value);
    }

    public function getEndpoint()
    {
        if ($this->getTestMode()) {
            return 'https://payments.sandbox.braintree-api.com/graphql';
        }
        return 'https://payments.braintree-api.com/graphql';
    }

    public function getData()
    {
        $card = $this->getCard();
        $card->validate();

        $graphQlQuery = <<<GRAPHQL
mutation TokenizeCreditCard(\$input: TokenizeCreditCardInput!) {
	tokenizeCreditCard(input: \$input) {
		paymentMethod {
			id
		}
	}
}
GRAPHQL;

        $data = [
            'query' => $graphQlQuery,
            'variables' => [
                'input' => [
                    'creditCard' => [
                        'number' => $card->getNumber(),
                        'expirationMonth' => $card->getExpiryMonth(),
                        'expirationYear' => $card->getExpiryYear(),
                        'cvv' => $card->getCvv(),
                    ],
                    'options' => [
                        'validate' => false,
                    ],
                ],
            ],
        ];

        return $data;
    }

    /**
     * Send the request with specified data.
     *
     * @param mixed $data The data to send
     *
     * @return \Omnipay\Braintree\Message\TokeniseCardResponse
     * @throws \Braintree\Exception
     */
    public function sendData($data)
    {
        // Decode the JWT client token
        $decodedClientToken = json_decode(base64_decode($this->getClientToken()), true);

        // Bearer token for the GraphQL API is in the decoded data under "authorizationFingerprint"
        $bearerToken = $decodedClientToken['authorizationFingerprint'];

        // POST the TokenizeCreditCard mutation to the GraphQL API with the card details
        $httpResponse = $this->httpClient->request(
            'POST',
            $this->getEndpoint(),
            [
                'Authorization' => 'Bearer ' . $bearerToken,
                'Content-Type' => 'application/json',
                'Braintree-Version' => '2016-10-07',
            ],
            json_encode($data)
        );

        // Check the response code
        $responseCode = $httpResponse->getStatusCode();
        if ($responseCode !== 200) {
            Util::throwStatusCodeException($responseCode);
        }

        $responseData = json_decode($httpResponse->getBody()->getContents(), true);
        // Ensure the response data has the expected token
        if (!isset($responseData['data']['tokenizeCreditCard']['paymentMethod']['id'])) {
            Util::throwGraphQLResponseException($responseData);
        }

        $token = $responseData['data']['tokenizeCreditCard']['paymentMethod']['id'];

        return new TokeniseCardResponse($this, ['id' => $token]);
    }
}
