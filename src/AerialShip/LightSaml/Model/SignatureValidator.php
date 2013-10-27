<?php

namespace AerialShip\LightSaml\Model;


use AerialShip\LightSaml\Error\InvalidXmlException;
use AerialShip\LightSaml\Error\SecurityException;
use AerialShip\LightSaml\Protocol;
use AerialShip\LightSaml\Security\KeyHelper;

class SignatureValidator extends Signature implements LoadFromXmlInterface
{
    /** @var \XMLSecurityDSig */
    protected $signature = null;

    /** @var string[] */
    protected $certificates;


    /**
     * @param \DOMElement $xml
     * @throws \AerialShip\LightSaml\Error\SecurityException
     * @throws \AerialShip\LightSaml\Error\InvalidXmlException
     * @return \DOMElement[]
     */
    function loadFromXml(\DOMElement $xml) {
        if ($xml->localName != 'Signature' || $xml->namespaceURI != Protocol::NS_XMLDSIG) {
            throw new InvalidXmlException('Expected Signature element and '.Protocol::NS_XMLDSIG.' namespace but got '.$xml->localName);
        }
        $this->signature = new \XMLSecurityDSig();
        $this->signature->idKeys[] = $this->getIDName();
        $this->signature->sigNode = $xml;
        $this->signature->canonicalizeSignedInfo();

        if (!$this->signature->validateReference()) {
            throw new SecurityException('Digest validation failed');
        }

        $this->certificates = array();
        $xpath = new \DOMXPath($xml instanceof \DOMDocument ? $xml : $xml->ownerDocument);
        $xpath->registerNamespace('ds', Protocol::NS_XMLDSIG);
        $list = $xpath->query('./ds:KeyInfo/ds:X509Data/ds:X509Certificate', $this->signature->sigNode);
        foreach ($list as $certNode) {
            $certData = trim($certNode->textContent);
            $certData = str_replace(array("\r", "\n", "\t", ' '), '', $certData);
            $this->certificates[] = $certData;
        }




        // output: xmlseclib signature + certificates from xml

        // get key from metadata as $pem where key type must be XMLSecurityKey::RSA_SHA1
//        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'public'));
//        $key->loadKey($pem);

        // SAML2/Utils.php validateSignature($info[], $key)
    }


    function validate(\XMLSecurityKey $key) {
        if ($this->signature == null) {
            return false;
        }
        if ($key->type != \XMLSecurityKey::RSA_SHA1) {
            throw new SecurityException('Key type must be RSA_SHA1 but got '.$key->type);
        }

        $xpath = new \DOMXPath($this->signature->sigNode instanceof \DOMDocument ? $this->signature->sigNode : $this->signature->sigNode->ownerDocument);
        $list = $xpath->query('./ds:SignedInfo/ds:SignatureMethod', $this->signature->sigNode);
        if ($list->length == 0) {
            throw new InvalidXmlException('Missing SignatureMethod element');
        }
        /** @var $sigMethod \DOMElement */
        $sigMethod = $list->item(0);
        if (!$sigMethod->hasAttribute('Algorithm')) {
            throw new InvalidXmlException('Missing Algorithm-attribute on SignatureMethod element.');
        }
        $algorithm = $sigMethod->getAttribute('Algorithm');

        if ($key->type === \XMLSecurityKey::RSA_SHA1 && $algorithm !== $key->type) {
            $key = KeyHelper::castKey($key, $algorithm);
        }

        $ok = $this->signature->verify($key);
        if (!$ok) {
            throw new SecurityException('Unable to verify Signature');
        }
        return true;
    }



}