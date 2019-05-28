<?php


require_once "src/Util/U.php";
require_once "src/Util/PS.php";
require_once "src/Util/LTI.php";
require_once "src/Util/LTI13.php";
require_once "src/Util/KVS.php";
require_once "src/Core/I18N.php";
require_once "src/OAuth/OAuthDataStore.php";
require_once "src/OAuth/TrivialOAuthDataStore.php";
require_once "src/OAuth/OAuthUtil.php";
require_once "src/OAuth/OAuthRequest.php";
require_once "src/OAuth/OAuthConsumer.php";
require_once "src/OAuth/OAuthServer.php";
require_once "src/OAuth/TrivialOAuthDataStore.php";
require_once "src/OAuth/OAuthSignatureMethod.php";
require_once "src/OAuth/OAuthSignatureMethod_HMAC_SHA1.php";
require_once "src/OAuth/OAuthSignatureMethod_HMAC_SHA256.php";
require_once "src/OAuth/OAuthException.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Blob/BlobUtil.php";

$dirroot = dirname(__FILE__).'/../';
$wwwroot = 'http://localhost:8888';
$CFG = new \Tsugi\Config\ConfigInfo($dirroot, $wwwroot);
$CFG->vendorinclude = dirname(__FILE__).'/../../include';

require_once "include/setup.php";


class LTI13Test extends PHPUnit_Framework_TestCase
{
    public $test_jwt_str = <<< EOF
        {
            "nonce": "172we8671fd8z",
            "iat": 1551290796,
            "exp": 1551290856,
            "iss": "https://lmsvendor.com",
            "aud": "PM48OJSfGDTAzAo",
            "sub": "3",
            "https://purl.imsglobal.org/spec/lti/claim/deployment_id": "689302",
            "https://purl.imsglobal.org/spec/lti/claim/lti1p1": {
                "user_id": "34212",
                "oauth_consumer_key": "179248902",
                "oauth_consumer_key_sign": "lWd54kFo5qU7xshAna6v8BwoBm6tmUjc6GTax6+12ps="
            }
        }
EOF
;

    public function testBasics() {
        $lj = json_decode($this->test_jwt_str);

        $base = \Tsugi\Util\LTI13::getLTI11TransitionBase($lj);
        $expected_base ="179248902&689302&https://lmsvendor.com&PM48OJSfGDTAzAo&1551290856&172we8671fd8z";
        $this->assertEquals($base, $expected_base);

        $secret = "my-lti11-secret";
        $key = "179248902";
        $signature = \Tsugi\Util\LTI13::signLTI11Transition($lj, $secret);
        $this->assertEquals($signature, "lWd54kFo5qU7xshAna6v8BwoBm6tmUjc6GTax6+12ps=");

        $lj->{\Tsugi\Util\LTI13::LTI11_TRANSITION_CLAIM}->oauth_consumer_key_sign = $signature;

        $check = \Tsugi\Util\LTI13::checkLTI11Transition($lj, $key, $secret);
        $this->assertTrue($check);

        $check = \Tsugi\Util\LTI13::checkLTI11Transition($lj, $key, "badsecret");
        $this->assertFalse($check);

        $check = \Tsugi\Util\LTI13::checkLTI11Transition($lj, "badkey", $secret);
        $this->assertEquals($check, 'LTI1.1 Transition key mis-match tsugi key=badkey');


    }

    // https://www.imsglobal.org/spec/lti/v1p3/migr#lti-1-1-migration-claim
    /*
        sign=base64(hmac_sha256(utf8bytes('179248902&689302&https://lmsvendor.com&PM48OJSfGDTAzAo&1551290856&172we8671fd8z'), utf8bytes('my-lti11-secret')))

     */

}
