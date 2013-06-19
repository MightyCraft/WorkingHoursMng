<?php
class NgWord
{
	var $encode = 'utf-8';
	
	function check($input, $words)
	{
		$input_org = $input;
		
		// 入力文字列の空白と改行を詰める
		// (例：NGワード「死ね」に対し、入力文字列中の「死 ね」「死\nね」等も対象となる。)
		$input = preg_replace('/(\s|　|\r?\n|\r|\n)+/', '', $input);
		
		// 入力文字列の全角カタカナ、全角ひらがなを半角カタカナに統一
		// (例：NGワード「エロ」に対し、入力文字列中の「ｴﾛ」「エﾛ」「ｴロ」「えろ」等も対象となる。)
		$input = mb_convert_kana($input, 'kha', $this->encode);
		
		// NGワードリストあり
		if(is_array($words) && 0 < count($words))
		{
			foreach($words as $word)
			{
				// 配列1つ目がNGワード、それ以降は許容ワード
				if(is_array($word))
				{
					$ng_word = array_shift($word);
				}
				else
				{
					$ng_word = $word;
				}
				
				$ng_word_org = $ng_word;
				$ng_word = mb_convert_kana($ng_word, 'kha', $this->encode);
				if('' === trim($ng_word)) continue;
				
				// 許容ワードあり
				if(is_array($word) && 0 < count($word))
				{
					// 許容ワードに含まれるNGワードを無効化した入力文字列を作成
					$input_ = array();
					foreach($word as $tmp)
					{
						$tmp = mb_convert_kana($tmp, 'kha', $this->encode);
						$tmp_ = str_replace($ng_word, str_repeat(' ', strlen($ng_word)), $tmp);
						$input_[] = str_replace($tmp, $tmp_, $input);
					}
					
					$input_tmp = '';
					for($i = 0; $i < strlen($input); $i++)
					{
						$flg = 0;
						foreach($input_ as $tmp)
						{
							if(' ' === substr($tmp, $i, 1))
							{
								$flg = 1;
								break;
							}
						}
						$input_tmp .= $flg ? ' ' : substr($input, $i, 1);
					}
				}
				else
				{
					// 許容ワードの指定無し
					$input_tmp = $input;
				}
				
				// NGワードにヒットした
				if(false !== strpos($input_tmp, $ng_word))
				{
					// 入力文字列上においてヒットした位置までの文字数(バイト数ではない)を取得
					$org_encode = mb_internal_encoding();
					mb_internal_encoding($this->encode);
					
					$check_org = preg_replace('/(\s|　|\r?\n|\r|\n)/', ' ', $input_org);
					$check_org2 = $check_org;
					$check_org = mb_convert_kana($check_org, 'kha', $this->encode);
					
					$check_str = $input_tmp;
					$check_str = preg_replace('/'. quotemeta($ng_word). '.*/', '', $check_str);
					$check_str = str_replace(str_repeat(' ', strlen($ng_word)), $ng_word, $check_str);
					
					$count1 = 0;
					$count2 = 0;
					$count3 = 0;
					$result_str = '';
					
					while(null != ($c1 = mb_substr($check_org, $count1++, 1)))
					{
						$c2 = mb_substr($check_org2, $count2++, 1);
						if(1 < mb_strlen(mb_convert_kana($c2, 'kha', $this->encode)))
						{
							$count1++;
						}
						
						if(' ' === $c1)
						{
							$result_str .= $c1;
						}
						else
						{
							$c3 = mb_substr($check_str, $count3++, 1);
							if(1 < mb_strlen(mb_convert_kana($c2, 'kha', $this->encode)))
							{
								$count3++;
							}
							
							if($c3 == '') break;
							if($c1 == $c2)
							{
								$result_str .= $c3;
							}
							else
							{
								$result_str .= $c2;
							}
						}
					}
					
					$result_strlen = mb_strlen($result_str);
					
					// NGワードにヒットした文字列を取得(NGワードリスト側の文字列ではなく、入力文字列側から取得)
					$ng_word_match = mb_substr($input_org, $result_strlen);
					$ng_word_match_check = mb_convert_kana($ng_word_match, 'kha', $this->encode);
					
					$return_string = '';
					$count1 = 0;
					$count2 = 0;
					$count3 = 0;
					while(null != ($c1 = mb_substr($ng_word_match_check, $count1++, 1)))
					{
						$c2 = mb_substr($ng_word_match, $count2++, 1);
						if(preg_match('/(\s|　|\r|\n)/',$c1))
						{
							$return_string .= $c1;
						}
						else
						{
							$return_string .= $c2;
							$c2_ = mb_convert_kana($c2, 'kha', $this->encode);
							if(mb_strlen($c2_) > mb_strlen($c2))
							{
								$count1++;
								$count3++;
							}
							
							$count3++;
							if(mb_strlen($ng_word) <= $count3) break;
						}
					}
					
					mb_internal_encoding($org_encode);
					
					return(
						array(
							'start'	=> $result_strlen,
							'word'	=> $return_string,
						)
					);
				}
			}
		}
		
		// NGワード一致無し
		return false;
	}
}
?>
