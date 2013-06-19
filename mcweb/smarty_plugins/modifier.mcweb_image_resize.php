<?php

function smarty_modifier_mcweb_image_resize($string, $scale)
{
	return intval($string) * $scale;
}