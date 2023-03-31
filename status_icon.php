<?php

// Output the appropriate status icon.
// A PHP file is used to output the image instead of a static image file, in order to avoid bugs in
// the module framework when getting the URL of a static file.


namespace Nottingham\REDCapUITweaker;


// Default to no icon.
$icon = null;

// Define icon images.
switch ( $_GET['icon'] )
{
	case 'gray':
		$icon = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMA' .
		        'AAsTAAALEwEAmpwYAAABKklEQVQ4y6WTQWoDMQxFnyRnQtbpAdJDdtFCu0ghmxy3TcHYsrqwZ0KaZJMK' .
		        'PhhsPX1JWD7fX4N/RAJ4eft4KPl42HcAwNf3iYig1kprQUQjoo1zFwQpJVSVp+327AAgIiilEBG01mht' .
		        'BgDEEOScmabpsgWAWp2IwN1HYiOCpfIcIkIp5Rpwrhq01uXuuDutdbiILLoCRPTHtRZqvUzsLkBVERFU' .
		        '9ZaDwL2Sc6aUMloJzAxVQdUQ0QVyBfj5OZFzXlqZK6W0QlUHSBG5A5iHONvtAFuSO8DuO3Cvf5IVszT2' .
		        'bqSUMLNxd3OILA7M5qodMjtI6exijuW0WiXcfaxJETk/7rPoLlIy1uvpGmBmbDab0YKiKksrXd3FNE23' .
		        '1/i82z3+G4+H/cPf+RfeHMAbbVBhpgAAAABJRU5ErkJggg';
		break;
	case 'red':
		$icon = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMA' .
		        'AAsTAAALEwEAmpwYAAABlUlEQVQ4y6WTsW4TURBFz8x7b9/aiR26FHR8E59AEdFQIEFLQYERKfJLNPkT' .
		        'RBVFkWN7d9/MUGyBwK6ckUZT3TP3jjSy+foleEFlgA8fP58lvrvdzACAx5sbbL/Hnp/xcSRUIWdCFSdA' .
		        'FOkrulqhywXX33/8dQBg+/0sNCOAiAB3IhyAEIMmxGGHY/9GALDdjmgNMyMiCAA3AifcgAAfcDsgQzkG' .
		        '+DTh7rgZIYDo7MAnsGGeY8CYIaUTgHDCDG/TvD0pogI2wLhF2gHEIee5jwDjhE8jPgxEGCTl9bcNgvH7' .
		        '0zvER1BQLRAnIrSnx/mI4wEwoCHRIBrJn0BANCFJZmf/A6aHB6LNeUUNzc6v928pfaBFkJSQLGhRJJ+4' .
		        'gW23EA2JCcmOJFBRRBXNCS0F7SrSLZDcHQMoCczAA1FHkyJF0a6gXSHVHq0X6OIS6fpjQLpaw7CHKRAZ' .
		        'kU7R2qG1on1F64K0vEJXr9D+8gRgtSIysBsQDM0JKQXpKlIq0vVIv0SXa/RifQx48/P+/G+8u92c/c5/' .
		        'AGdvvQYNhR4bAAAAAElFTkSuQmCC';
		break;
	case 'reds':
		$icon = 'iVBORw0KGgoAAAANSUhEUgAAABUAAAAQCAYAAAD52jQlAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMA' .
		        'AAsTAAALEwEAmpwYAAAByElEQVQ4y7WUvW5UQQyFP3tm7tzdZBM6CjrehQLR8ggUEU0KEFBSULAoFHkl' .
		        'Gp4CiCgQVRRFm929P7YprpQggWCFwkijceNP5xxrLMs3r4NbPhng+NnLf2o+fb/k+Pkrzj5/4uvZFx48' .
		        'fMTpyXKCAlwcHWGbDXZ1hfc9oQo5E6o4AaJIW9HFAp3PuPvu5M9KAWyzmWBmBBAR4E6EAxBiMAqxXePY' .
		        '3+0D2HpNjCNmRkQQAG4ETrgBAd7htkW6shvUhwF3x80IAUQnpT6AddPbB/QZUtoRGk6Y4eMwqUyKqIB1' .
		        '0K+QcQvikPN0d4L2Az70eNcRYZCUe2+XCMb3F08Q70FBtUDsaH+8vJgG1W8BA0YkRoiR5JcgIJqQJJOD' .
		        'XaDD+TkxTvmJGpqdb08fU9pAiyApIVnQoki+yTRi+jv+2+mvVhAjEgOSHUmgoogqmhNaCtpUpJkhubkG' .
		        '1FqJCMpPOd9UJYEZeCDqaFKkKNoUtCmk2qJ1D53tI0173db3PSKCjeOv0HR4AN0GhkCkRxpFa4PWirYV' .
		        'rTPS/BBd3EHb/d0yTYsFkYF1h2BoTkgpSFORUpGmRdo5Oj9A9w52g97/8PHWtpT8j9X3Aw0H0/7N6Si2' .
		        'AAAAAElFTkSuQmCC';
		break;
	case 'blues':
		$icon = 'iVBORw0KGgoAAAANSUhEUgAAABYAAAAQCAMAAAAlM38UAAABDlBMVEUAAAADK9UDLtYDL9YDMdcIMNYD' .
		        'NNgDN9kDOdoDOtsDO9sCPdsHPtsCQ94CRd8CSuACSuECTeIIUNsdTN0CVeUoS9sCVuUCWeYCXegCX+kB' .
		        'Zes1V94BZusBaOwMb+MBcfABdfEBefNFa+MYetoRgOREdeZFdeZHdeZCe+lEfepAhe08jvGFhYWJhYGI' .
		        'iIiYmJiZmZmEmOuFm+h7o+2goKCsrKytra2YsNGesu2euNGjus23t7e5ubmavfKbwfPCwsLDw8PFxMLF' .
		        'xcXGxsbMzMzKz9TV1dXW19rb29vc3Nzd3d3i4uLj4uLj4+Pl5eXn5+fu7u7v7+/19fX39/f49/f4+Pj5' .
		        '+fn7+/v8/Pz9/f2zlA/xAAAAAXRSTlMAQObYZgAAAAFiS0dEAIgFHUgAAAAJcEhZcwAACxMAAAsTAQCa' .
		        'nBgAAADkSURBVBjTY2Bg0NV1tDbQ0TGw9tC38QQiBgjQ9QFKBAf7OphYB5jYBAIRSDRANyQEJOEVGmZv' .
		        'GuBsFghEQGFr3RBtW7CES4S9NYOzDQgxeJjohmhpqFmAJcJMPCJNPIGIwdpBN0RTXUla1AgsAVNu4Ksb' .
		        'oirMyszJJ2gMlvDTD/LTZ9AJ1g3hYWLiFhCTlJG1A0qE6/mF64GF2Rg5+MVl5BWVXcHC/kBhkCFMLLwi' .
		        'UgoqVlBDgoGGgKxkZBeQkLNEsRLkQEMuIXM0B4K8E+wOFnRC8g7c827BKJ6HBZU3alBhDVgAEhFHGCY+' .
		        'KpIAAAAASUVORK5CYII';
		break;
}

// Return 404 status if invalid icon specified.
if ( $icon === null )
{
	header( 'HTTP/1.1 404 Not Found' );
	exit;
}

// Override REDCap caching headers, to allow caching.
// Return a 304 status if icon unchanged from cached version.
header( 'Pragma: ' );
header( 'Expires: ' );
header( 'Cache-Control: max-age=2419200' );

$etag = substr( sha1( $icon ), 0, 12 );
if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag )
{
	header( 'HTTP/1.1 304 Not Modified' );
	exit;
}

// Output icon image.
header( 'Content-Type: image/png' );
header( 'ETag: ' . $etag );
echo base64_decode( $icon );
