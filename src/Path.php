<?php
/**
 * Author: iassasin <iassasin@yandex.ru>
 * License: beerware
 * Use for good
 */

namespace Iassasin\Easyroute;

class Path {
	public $regex;
	public $varGroups;

	private function __construct(string $regex, array $varGroups){
		$this->regex = $regex;
		$this->varGroups = $varGroups;
	}

	public static function parse(string $rx){
		$len = strlen($rx);
		$pos = $spos = 0;
		$group = 0;
		$resrx = '/^';
		$resvars = [];

		if ($len < 1){
			return new static('/^$/', []);
		}

		while ($pos < $len){
			switch ($rx[$pos]){
				case '\\':
					$pos += 2;
					break;

				case '/':
					$resrx .= substr($rx, $spos, $pos - $spos);
					$resrx .= '\\/';
					$spos = ++$pos;
					break;

				case '(':
					++$group;
					++$pos;
					break;

				case ':':
					$resrx .= substr($rx, $spos, $pos - $spos);
					$spos = ++$pos;

					while ($pos < $len){
						$ch = $rx[$pos];
						if ($ch >= 'a' && $ch <= 'z' || $ch >= 'A' && $ch <= 'Z' || $ch >= '0' && $ch <= '9' || $ch == '_'){
							++$pos;
						} else {
							break;
						}
					}

					if ($pos == $spos){
						$resrx .= ':';
						break;
					}

					$varname = substr($rx, $spos, $pos - $spos);
					$spos = $pos;
					$resvars[] = $varname;

					if ($pos < $len && $rx[$pos] == '('){
						$spos = ++$pos;
						$resrx .= '(?P<'.$varname.'>';
					} else {
						if ($pos < $len && $rx[$pos] == ':'){
							$spos = ++$pos;
						}

						$resrx .= '(?P<'.$varname.'>[^\/]+)';
					}

					break;

				default:
					++$pos;
					break;
			}
		}

		$resrx .= substr($rx, $spos, $pos - $spos);
		$resrx .= '$/';

		return new static($resrx, $resvars);
	}
}
