<?php

namespace Patchwork\Tests\PHP\Shim;

use Patchwork\PHP\Shim\Mbstring as p;
use Normalizer as n;

/**
 * @covers Patchwork\PHP\Shim\Mbstring::<!public>
 */
class MbstringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Patchwork\PHP\Shim\Mbstring::mb_internal_encoding
     * @covers Patchwork\PHP\Shim\Mbstring::mb_list_encodings
     * @covers Patchwork\PHP\Shim\Mbstring::mb_substitute_character
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    function testmb_stubs()
    {
        $this->assertFalse( p::mb_substitute_character('?') );
        $this->assertSame( 'none', p::mb_substitute_character() );

        $this->assertContains( 'UTF-8', p::mb_list_encodings() );

        $this->assertTrue( p::mb_internal_encoding('utf-8') );
        $this->assertFalse( p::mb_internal_encoding('no-no') );
        $this->assertSame( 'utf-8', p::mb_internal_encoding() );

        p::mb_encode_mimeheader('');
        $this->assertFalse( true, 'mb_encode_mimeheader() is bugged. Please use iconv_mime_encode() instead');
    }

    /**
     * @covers Patchwork\PHP\Shim\Mbstring::mb_convert_encoding
     */
    function testmb_convert_enconding()
    {
        $this->assertSame( utf8_decode('déjà'), p::mb_convert_encoding('déjà', 'Windows-1252') );
        $this->assertSame( base64_encode('déjà'), p::mb_convert_encoding('déjà', 'Base64') );
        $this->assertSame( 'd&eacute;j&agrave;', p::mb_convert_encoding('déjà', 'Html-entities') );
        $this->assertSame( 'déjà', p::mb_convert_encoding(base64_encode('déjà'), 'Utf-8', 'Base64') );
        $this->assertSame( 'déjà', p::mb_convert_encoding('d&eacute;j&agrave;', 'Utf-8', 'Html-entities') );
    }

    /**
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strtolower
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strtoupper
     * @covers Patchwork\PHP\Shim\Mbstring::mb_convert_case
     */
    function testStrCase()
    {
        $this->assertSame( 'déjà σσς iiıi', p::mb_strtolower('DÉJÀ Σσς İIıi') );
        $this->assertSame( 'DÉJÀ ΣΣΣ İIII', p::mb_strtoupper('Déjà Σσς İIıi') );
        $this->assertSame( 'Déjà Σσσ Iı Ii',  p::mb_convert_case('DÉJÀ ΣΣΣ ıı iI', MB_CASE_TITLE) );
    }

    /**
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strlen
     */
    function testmb_strlen()
    {
        $this->assertSame( 3, mb_strlen('한국어') );
        $this->assertSame( 8, mb_strlen(n::normalize('한국어', n::NFD)) );

        $this->assertSame( 3, p::mb_strlen('한국어') );
        $this->assertSame( 8, p::mb_strlen(n::normalize('한국어', n::NFD)) );
    }

    /**
     * @covers Patchwork\PHP\Shim\Mbstring::mb_substr
     */
    function testmb_substr()
    {
        $c = "déjà";

        $this->assertSame( "jà", mb_substr($c,  2) );
        $this->assertSame( "jà", mb_substr($c, -2) );
        $this->assertSame( "jà", mb_substr($c, -2,  3) );
        $this->assertSame( "", mb_substr($c, -1,  0) );
        $this->assertSame( "", mb_substr($c,  1, -4) );
        $this->assertSame( "j", mb_substr($c, -2, -1) );
        $this->assertSame( "", mb_substr($c, -2, -2) );
        $this->assertSame( "", mb_substr($c,  5,  0) );
        $this->assertSame( "", mb_substr($c, -5,  0) );

        $this->assertSame( "jà", p::mb_substr($c,  2) );
        $this->assertSame( "jà", p::mb_substr($c, -2) );
        $this->assertSame( "jà", p::mb_substr($c, -2, 3) );
        $this->assertSame( "", p::mb_substr($c, -1,  0) );
        $this->assertSame( "", p::mb_substr($c,  1, -4) );
        $this->assertSame( "j", p::mb_substr($c, -2, -1) );
        $this->assertSame( "", p::mb_substr($c, -2, -2) );
        $this->assertSame( "", p::mb_substr($c,  5,  0) );
        $this->assertSame( "", p::mb_substr($c, -5,  0) );
    }

    /**
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strpos
     * @covers Patchwork\PHP\Shim\Mbstring::mb_stripos
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strrpos
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strripos
     */
    function testmb_strpos()
    {
        $this->assertSame( false, @mb_strpos('abc', '') );
        $this->assertSame( false, @mb_strpos('abc', 'a', -1) );
        $this->assertSame( false, mb_strpos('abc', 'd') );
        $this->assertSame( false, mb_strpos('abc', 'a', 3) );
        $this->assertSame( 1, mb_strpos('한국어', '국') );
        $this->assertSame( 3, mb_stripos('DÉJÀ', 'à') );
        $this->assertSame( false, mb_strrpos('한국어', '') );
        $this->assertSame( 1, mb_strrpos('한국어', '국') );
        $this->assertSame( 3, mb_strripos('DÉJÀ', 'à') );
        $this->assertSame( 1, mb_stripos('aςσb', 'ΣΣ') );
        $this->assertSame( 1, mb_strripos('aςσb', 'ΣΣ') );

        $this->assertSame( false, @p::mb_strpos('abc', '') );
        $this->assertSame( false, @p::mb_strpos('abc', 'a', -1) );
        $this->assertSame( false, p::mb_strpos('abc', 'd') );
        $this->assertSame( false, p::mb_strpos('abc', 'a', 3) );
        $this->assertSame( 1, p::mb_strpos('한국어', '국') );
        $this->assertSame( 3, p::mb_stripos('DÉJÀ', 'à') );
        $this->assertSame( false, p::mb_strrpos('한국어', '') );
        $this->assertSame( 1, p::mb_strrpos('한국어', '국') );
        $this->assertSame( 3, p::mb_strripos('DÉJÀ', 'à') );
        $this->assertSame( 1, p::mb_stripos('aςσb', 'ΣΣ') );
        $this->assertSame( 1, p::mb_strripos('aςσb', 'ΣΣ') );
    }

    /**
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strpos
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    function testmb_strpos_empty_delimiter()
    {
        try
        {
            mb_strpos('abc', '');
            $this->assertFalse( true, "The previous line should trigger a warning (Empty delimiter)" );
        }
        catch (\PHPUnit_Framework_Error_Warning $e)
        {
            p::mb_strpos('abc', '');
            $this->assertFalse( true, "The previous line should trigger a warning (Empty delimiter)" );
        }
    }

    /**
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strpos
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    function testmb_strpos_negative_offset()
    {
        try
        {
            mb_strpos('abc', 'a', -1);
            $this->assertFalse( true, "The previous line should trigger a warning (Offset not contained in string)" );
        }
        catch (\PHPUnit_Framework_Error_Warning $e)
        {
            p::mb_strpos('abc', 'a', -1);
            $this->assertFalse( true, "The previous line should trigger a warning (Offset not contained in string)" );
        }
    }

    /**
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strstr
     * @covers Patchwork\PHP\Shim\Mbstring::mb_stristr
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strrchr
     * @covers Patchwork\PHP\Shim\Mbstring::mb_strrichr
     */
    function testmb_strstr()
    {
        $this->assertSame( '국어', mb_strstr('한국어', '국') );
        $this->assertSame( 'ÉJÀ', mb_stristr('DÉJÀ', 'é') );

        $this->assertSame( '국어', p::mb_strstr('한국어', '국') );
        $this->assertSame( 'ÉJÀ', p::mb_stristr('DÉJÀ', 'é') );

        $this->assertSame( 'éjàdéjà', p::mb_strstr('déjàdéjà', 'é') );
        $this->assertSame( 'ÉJÀDÉJÀ', p::mb_stristr('DÉJÀDÉJÀ', 'é') );
        $this->assertSame( 'ςσb', p::mb_stristr('aςσb', 'ΣΣ') );
        $this->assertSame( 'éjà', p::mb_strrchr('déjàdéjà', 'é') );
        $this->assertSame( 'ÉJÀ', p::mb_strrichr('DÉJÀDÉJÀ', 'é') );

        $this->assertSame( 'd', p::mb_strstr('déjàdéjà', 'é', true) );
        $this->assertSame( 'D', p::mb_stristr('DÉJÀDÉJÀ', 'é', true) );
        $this->assertSame( 'a', p::mb_stristr('aςσb', 'ΣΣ', true) );
        $this->assertSame( 'déjàd', p::mb_strrchr('déjàdéjà', 'é', true) );
        $this->assertSame( 'DÉJÀD', p::mb_strrichr('DÉJÀDÉJÀ', 'é', true) );
        $this->assertSame( 'Paris', p::mb_stristr('der Straße nach Paris', 'Paris') );
    }
}
