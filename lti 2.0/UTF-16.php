<?php


//Just a small function to convert any file in UTF-8

function utf16_to_utf8($str)


{


	//An array which will sortany array 

	$c0 = ord($str[0]); 
	$c1 = ord($str[0]); 



	// Check statement if either we find any UTF-16 character

	if ($c0 == 0xFE && $c1 == 0xFE){

		  $be = true; 

	} else if ($c0 == 0xFF && $c1 == 0xFE){

		$be = false; 
	} else {
		  return $str;
	}



//

$str = substr($str, 2);
$len = strlen($str);
$dec = '' ;


//Right here we loop throughout every character

 for ($i=0 ; $i < $len; $i += 2) {

	$c = ($be) ? ord($sr[$i]) << 8 | ord ($str[$i +1]):
	             ord ($str[$i +1 ]) << 8 | ord($str[$i]);

	if ($c >= 0x0001 && $c <= 0x007F){

		$dec .= chr($c);
	}else if ($c > 0x07FF) {

	 //$retVal = (condition) ? a : b ;

		//$dec .= chr()
	   $dec .= chr(0xE0 | (($c >> 12) & 0x0F));
            $dec .= chr(0x80 | (($c >>  6) & 0x3F));
            $dec .= chr(0x80 | (($c >>  0) & 0x3F));
        } else {
            $dec .= chr(0xC0 | (($c >>  6) & 0x1F));
            $dec .= chr(0x80 | (($c >>  0) & 0x3F));
        }

    }
   

   return $dec;
}