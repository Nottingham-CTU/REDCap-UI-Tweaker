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
	case 'grays':
		$icon = 'iVBORw0KGgoAAAANSUhEUgAAABYAAAAQCAYAAAAS7Y8mAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMA' .
		        'AAsTAAALEwEAmpwYAAAAB3RJTUUH6QoQCyY1fg4HMgAAAB1pVFh0Q29tbWVudAAAAAAAQ3JlYXRlZCB3' .
		        'aXRoIEdJTVBkLmUHAAABWElEQVQ4y7WUzW4CMQyExz8EcaYPQB+zh5543XJqgYbEcQ/ZzQYIqoREJEsT' .
		        'rfbzZByF9p8fjles/eeH/57PT1X/763WucH3zxHujpwzSnG4F7iXSdcCHKoKZsbbdgsA+DochrqB3R0p' .
		        'Jbg7SikoZQYDgE8FxBgRQmgnNrOhbuCcDe4OM5uABe5oTudFREgptf0j3cCLS0cptcwMZoZSalMiajWv' .
		        'y+Uy1F0UFZJzQs7XwOoaYGYQEZgZfYQj3Tl2mGXEGJFSmiJxiAiYCcwCIm7wJRoe6gY+nY6IMbZIZmeq' .
		        'KzDz1IBBdA1ercJQ3w1vPk4FS4NWsNw5VtWxXq5KvoEyRHS6twJVhYhM33rHOtTd8NAci8wuK3x2rLq4' .
		        '/i8K7ruZ2XSdGEQLpGZdXasK1usF8Eg3sIhgs9m06TJTi6RWdR1CuHL8SLco3ne7px6x0OXaa3rVs/kH' .
		        '88ssdjGium8AAAAASUVORK5CYII';
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
	case 'yellow':
		$icon = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMA' .
		        'AAsTAAALEwEAmpwYAAAAB3RJTUUH5wYCBwYnFMO42QAAARZJREFUOMvFkrFKw1AYRk+uN9ZsgmbT1Qfw' .
		        'BQSVPkShg7Pg6uAgoWToU3Qo9D2EDootKCXi4JSCQoOTW2P5HJK2lLRR6+AP33i/c/57L5RMMwzUDAOx' .
		        'zjTDQMM41jCOS0tsWYnv+/yJrvdzaXRRamFL6W8DmBj8PX8N+suR9HwsRafSoLrSwqykj11ILUws4P7s' .
		        'Pmb0p6r0eCL1D6XegXS3I93vLrWwS+mvEYxHYFLYIIv55lVm9K4r3SB1kW6RekgPSAOkiIKFKdA/U3By' .
		        'arZ+li3AK1rYKb1WP6PS97LDhrm6BSp5XKh8eNTqMYAur64ds0Dfb8wLpiUusJkXeMB2Y8HCmdJ/+22T' .
		        'JKHTbmUrdNot/m2+AIW3oxjAnQ1nAAAAAElFTkSuQmCC';
		break;
	case 'yellows':
		$icon = 'iVBORw0KGgoAAAANSUhEUgAAABUAAAAQCAYAAAD52jQlAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMA' .
		        'AAsTAAALEwEAmpwYAAAAB3RJTUUH5wYCBw84SAkOZQAAAB1pVFh0Q29tbWVudAAAAAAAQ3JlYXRlZCB3' .
		        'aXRoIEdJTVBkLmUHAAABVElEQVQ4y9XUP0tCURjH8a/Xa3+2oO5WayCIYL2AoMIXITg0B60NDSHi4Ktw' .
		        'EHwF0dAWOPRHsRDFQSiuUKE0VYsmv4arV2+lXmnqgQfOeeA858MD5wSYEtlMSuP7WGybSqUMwPHJaWDS' .
		        'OXNaw0TyAIBCPkciecDH+xsA4UgUQJMam9OklmV51sOm43Xfkc2k1LJt6fVQah+pZdsa1hr1mi7Oz9za' .
		        'XFLLsuC5Cn0Da936m9ZVNnekxq5U25eq8bm0xkRlNwQ9E/omEHJl3+fsX1mPS/d7UnlLKm1K16vS7Zpv' .
		        'rfmr8qkG3TYYPQjipIFH62u2rrIYki6RikhXSCWkO6QqUg1fWuOH8rMHgYHOGaeTS8Ay/mc7UjJS3iBV' .
		        'HJ2aSC2kF6T2bK3huW0j7SiNQQYHygVg0ZGykp6pDQzf+LxPr9PpeP4E+/GBcCRKIZ9zmvIf4gtyASus' .
		        'o/tTzgAAAABJRU5ErkJggg';
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
	case 'orange':
		$icon = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMA' .
		        'AAsTAAALEwEAmpwYAAAAB3RJTUUH5wYCCAwJOaYaoQAAAaNJREFUOMvFkr1rU1EYxn/n4za3Se5g5bop' .
		        'FRTJ4OAiLnWyg5uLCIUOdeofEFDMIBmCFFT8GNzsUChSHDp1KwounaRLV4UErZLQ2EHT5J5zXodSTdJo' .
		        '7eSzHN7z8XKe3/MqhrRQqwp/0Z3KfdVf2+HHM7NzHCHpb2KHT9M05TiyozZz78q/i2D2V+3pTj38twZ8' .
		        'fQ9egzcQNEEMgoapw1f1sP/c62vgLDhLcJbMRfRchLrxAvfyFjOzcwOg7SH/ziJe453FBYMPhkKpBCcn' .
		        'iW8+IU3S0T/4hThYvB8jyxTdnTaFUgm5UiF8+8zuo+mjGajJS3TfrLDXauF6gWT+FQpBbz7Dqs7oKBZq' .
		        'VWnU6yLrZRGfiW9+kC+3Y/GtjyLeiayX5Xv1rHRq56Tz4Lw06nU54KD7/ffWHiPtBnriDKeeb6NPnIa3' .
		        'd9nbWCUqjhMlecaS/MCsDDDwTtG+d4Gw00DFCbL7ic7GKjYfYws5TNGgi/wZorlexQdFq3IRv73Fj6dX' .
		        'MbkIE0eoOMB4BpfnB5kd5H/cEW42mywvLe6nsLy0yH/TT5wJq8/87V37AAAAAElFTkSuQmCC';
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
