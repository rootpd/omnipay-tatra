<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\Gateway;

class ChargeRequest extends AbstractSoapRequest
{
    public function getTransactionType()
    {
        return $this->getParameter('transactionType');
    }

    public function setTransactionType($value)
    {
        return $this->setParameter('transactionType', $value);
    }

    public function getWs()
    {
        return $this->getParameter('ws');
    }

    public function setWs($value)
    {
        return $this->setParameter('ws', $value);
    }

    public function getTerminalId()
    {
        return $this->getParameter('terminalId');
    }

    public function setTerminalId($value)
    {
        return $this->setParameter('terminalId', $value);
    }

    public function getParentTransactionId()
    {
        return $this->getParameter('parentTransactionId');
    }

    public function setParentTransactionId($value)
    {
        return $this->setParameter('parentTransactionId', $value);
    }

    public function getReferedCardId()
    {
        return $this->getParameter('referedCardId');
    }

    public function setReferedCardId($value)
    {
        return $this->setParameter('referedCardId', $value);
    }

    public function getE2eReference()
    {
        return $this->getParameter('e2eReference');
    }

    public function setE2eReference($value)
    {
        return $this->setParameter('e2eReference', $value);
    }

    public function getSubmerchantId()
    {
        return $this->getParameter('submerchantId');
    }

    public function setSubmerchantId($value)
    {
        return $this->setParameter('submerchantId', $value);
    }

    public function getLocation()
    {
        return $this->getParameter('location');
    }

    public function setLocation($value)
    {
        return $this->setParameter('location', $value);
    }

    public function getCity()
    {
        return $this->getParameter('city');
    }

    public function setCity($value)
    {
        return $this->setParameter('city', $value);
    }

    public function getAlpha2CountryCode()
    {
        return $this->getParameter('alpha2CountryCode');
    }

    public function setAlpha2CountryCode($value)
    {
        return $this->setParameter('alpha2CountryCode', $value);
    }

    public function getData()
    {
        $this->validate('terminalId', 'ws', 'amount', 'transactionId', 'transactionType', 'referedCardId',  'currency');

        if (in_array($this->getTransactionType(), [Gateway::TRANSACTION_TYPE_PREAUTH_CONFIRM, Gateway::TRANSACTION_TYPE_PREAUTH_CANCEL, Gateway::TRANSACTION_TYPE_CHARGEBACK])) {
            $this->validate('parentTransactionId');
        }

        $data = parent::getData();
        $data = array_merge($data, [
            'transactionType' => $this->getTransactionType(),
            'transactionId' => $this->getTransactionId(),
            'parentTransactionId' => $this->getParentTransactionId(),
            'referedCardId' => $this->getReferedCardId(),
            'merchantId' => $this->getWs(),
            'terminalId' => $this->getTerminalId(),
            'amount' => $this->getAmount(),
            'cc' => $this->getCurrency(),
            'vs' => $this->getVs(),
            'ss' => $this->getSs(),
            'e2eReference' => $this->getE2eReference(),
            'submerchantId' => $this->getSubmerchantId(),
            'location' => $this->getLocation(),
            'city' => $this->getCity(),
            'alpha2CountryCode' => $this->getAlpha2CountryCode(),
        ]);

        if (empty($data['e2eReference'])) {
            $this->validate('ss', 'vs');
        }

        if (empty($data['ss']) && empty($data['vs'])) {
            $this->validate('e2eReference');
        }

        if (!empty($data['submerchantId']) || !empty($data['location']) || !empty($data['city']) || !empty($data['alpha2CountryCode'])) {
            $this->validate('submerchantId', 'location', 'city', 'alpha2CountryCode');
        }

        return $data;
    }

    public function sendData($data)
    {
        if ($this->getTestmode())
        {
            if ((int)$this->getReferedCardId() % 2 == 0) {
                return $this->response = new ChargeResponse($this,
                    ['transactionId' => $data['transactionId'], 'transactionStatus' => '02', 'transactionApproval' => '123']);
            }
            return $this->response = new ChargeResponse($this,
                ['transactionId' => $data['transactionId'], 'transactionStatus' => '00', 'transactionApproval' => '123']);
        }

        $req = new \stdClass();
        $req->transactionId = $data['transactionId'];
        $req->referedCardId = $data['referedCardId'];
        $req->merchantId = $data['merchantId'];
        $req->terminalId = $data['terminalId'];
        $req->amount = $data['amount'];
        $req->parentTransactionId = $data['parentTransactionId'];
        $req->cc = $data['cc'];

        $transactionIdentificator = new \stdClass();

        if (!empty($data['vs']) && !empty($data['ss'])) {
            $symbols = new \stdClass();
            $symbols->variableSymbol = $data['vs'];
            $symbols->specificSymbol = $data['ss'];
            $transactionIdentificator->symbols = $symbols;
        } else {
            $transactionIdentificator->e2eReference = $data['e2eReference'];
        }

        $req->transactionIdentificator = $transactionIdentificator;

        if (!empty($data['submerchantId']) && !empty($data['location']) && !empty($data['city']) && !empty($data['alpha2CountryCode'])) {
            $ipspData = new \stdClass();
            $ipspData->submerchantId = $data['submerchantId'];
            $ipspData->location = $data['location'];
            $ipspData->city = $data['city'];
            $ipspData->alpha2CountryCode = $data['alpha2CountryCode'];
            $req->ipspData = $ipspData;
        }

        $request = new \stdClass();
        $request->req = $req;
        $request->transactionType = $data['transactionType'];

        $client = $this->getSoapClient();
        $response = $client->doCardTransaction($request);

        return $this->response = new CardTransactionResponse($this, [
            'transactionId' => $response->res->transactionId,
            'transactionStatus' => $response->res->transactionStatus,
            'transactionApproval' => $response->res->transactionStatus,
        ]);
    }
}
