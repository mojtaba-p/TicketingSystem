<?php

require_once TS_INC . "vendor/autoload.php";
use LanguageDetection\Language;


//add action ajaxes 

/**
 * used for persian words
 * 
 * @param  string $word. word from user input.
 * @return string suggested word
 */
function wpts_spell_help_init( $word ){
	$ln = new Language(['fa','en']);
	$lang = $ln->detect($word)->bestResults()->close();
	$lang = array_key_first($lang);
	return wpts_spell_help($word, $lang);
}

/**
 * loads dictionaries and them related stop words
 * 
 * @param  string $word  	  that must spell check
 * @param  string $language that word have
 * 
 * @return string
 */
function wpts_spell_help($word, $language = "fa" ) {
	// default language is persian or farsi
	$language = ! empty( $language ) ? $language : 'fa';
	// location of stop words of language
	$stop_words_location = TS_SPL . 'stop-words/' . $language . '-stopwords.json';

	// stop if stopwords not found
	if ( ! file_exists( $stop_words_location ) ) {
		wp_die( "مسیر غلط فایل stopword" );
	}

	$stop_words_json = file_get_contents( $stop_words_location );
	$stop_words      = json_decode( $stop_words_json, true );

	$dictionary_location = TS_SPL . $language . "-dictionary.json";
	// stop if dictionary not found.
	if ( ! file_exists( $dictionary_location ) ) {
		wp_die( "مسیر غلط فایل دیکشنری" );
	}

	$dictionary_json  = file_get_contents( $dictionary_location );
	$dictionary_words = json_decode( $dictionary_json, true );

	$max_value = max($dictionary_words);

	if ( in_array( trim($word), $dictionary_words ) ) {
		return $word;
	}

	$percent = 0;
	return wpts_closest_word( $word, $dictionary_words, $max_value, $stop_words, $percent );
}

/**
 * copmute closest word in dictionary to the given word.
 * 
 * @param string  $input. 	   word to check.
 * @param array   $words. 	   words in dictionary.
 * @param integer $max_value.  highest frequency in dictionary.
 * @param array   $stop_words. words of stopwords.
 * @param integer $percent.	   percentage of occuracy.
 * 
 * @return struing $closest.   closest word.
 */
function wpts_closest_word( $input, $words, $max_value = 1, $stopwords = [], &$percent = null ) {
	$shortest = -1;

	// check if word is correct
	if( $words[$input] ){
		return $input;
	}

	// if input is stop word return input.
	if ( in_array( $input, $stopwords ) ) {
		$percent = 1;

		return $input;
	}

	foreach ( $words as $word => $value ) {
		if ( strlen(trim($word)) < 2){
			continue;
		}

		// add value of word frequency 
		$added_value =  ( 1 - ( $value ) / $max_value ) * 1.5;
		$lev = wpts_levenshtein_utf8( $input, $word ) + $added_value;

		if ( in_array( $word, $stopwords ) ) {
			continue;
		}

		if ( $lev == 0 ) {
			$closest  = $word;
			$shortest = 0;
			break;
		}


		if ( $lev <= $shortest || $shortest < 0 ) {

			$closest  = $word;
			$shortest = $lev;

		}

	}

	$percent = 1 - wpts_levenshtein_utf8( $input, $closest ) / max( strlen( $input ), strlen( $closest ) );

	return $closest;
}


/**
 * compute levenshtein for utf8 strings
 * @param string $s1. first string.
 * @param string $s2. second string.
 */
function wpts_levenshtein_utf8( $s1, $s2 ) {
	$charMap = array();
	$s1      = wpts_utf8_to_extended_ascii( $s1, $charMap );
	$s2      = wpts_utf8_to_extended_ascii( $s2, $charMap );

	return levenshtein( $s1, $s2 );
}


function wpts_utf8_to_extended_ascii( $str, &$map ) {
	// find all multibyte characters (cf. utf-8 encoding specs)
	$matches = array();
	if ( ! preg_match_all( '/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches ) ) {
		return $str;
	} // plain ascii string

	// update the encoding map with the characters not already met
	foreach ( $matches[0] as $mbc ) {
		if ( ! isset( $map[ $mbc ] ) ) {
			$map[ $mbc ] = chr( 128 + count( $map ) );
		}
	}

	// finally remap non-ascii characters
	return strtr( $str, $map );
}