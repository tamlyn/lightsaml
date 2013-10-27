<?php

namespace AerialShip\LightSaml;


final class Protocol
{
    const SAML2 = 'urn:oasis:names:tc:SAML:2.0:protocol';

    const NS_METADATA = 'urn:oasis:names:tc:SAML:2.0:metadata';
    const NS_XMLDSIG = 'http://www.w3.org/2000/09/xmldsig#';
    const NS_ASSERTION = 'urn:oasis:names:tc:SAML:2.0:assertion';

    const XMLSEC_TRANSFORM_ALGORITHM_ENVELOPED_SIGNATURE = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    protected function __construct() { }
}