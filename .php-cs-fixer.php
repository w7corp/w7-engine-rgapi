<?php

$finder = PhpCsFixer\Finder::create()
	->files()
	->name('*.php')
	->exclude('vendor')
	->in(__DIR__)
	->ignoreDotFiles(true)
	->ignoreVCS(true);

$fixers = [
	'@PSR2' => true,
	'braces' => ['position_after_functions_and_oop_constructs' => 'same'], //大括号放一行
	'concat_space' => ["spacing" => "one"], //操作符之间一个空格
	'no_empty_statement' => true, //多余的分号
	'no_extra_blank_lines' => true, //多余空白行
	'ternary_operator_spaces' => true,  //标准化三元运算的格式
	'whitespace_after_comma_in_array' => true, // 在数组声明中，每个逗号后必须有一个空格
	'binary_operator_spaces' => ['default' => 'single_space'], // 二进制运算符包含在空格中
	'concat_space' => ['spacing' => 'one'], // 连接符两边空格

];
return (new PhpCsFixer\Config())
	->setRules($fixers)
	->setFinder($finder)
//	->setIndent("\t")
	->setUsingCache(false);
