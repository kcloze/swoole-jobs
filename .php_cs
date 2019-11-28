<?php

/*
 * This file is part of Swoole-jobs
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$header = <<<'EOF'
This file is part of Swoole-jobs
(c) kcloze <pei.greet@qq.com>
This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony'                   => true,
        '@Symfony:risky'             => true,
        'array_syntax'               => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        // one should use PHPUnit methods to set up expected exception instead of annotations
        'general_phpdoc_annotation_remove'      => ['expectedException', 'expectedExceptionMessage', 'expectedExceptionMessageRegExp'],
        //'header_comment'                        => ['header' => $header],
        //'heredoc_to_nowdoc'                     => true,
        'no_extra_consecutive_blank_lines'      => ['break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block'],
        'no_unreachable_default_argument_value' => true,
        'no_useless_else'                       => true,
        'no_useless_return'                     => true,
        'ordered_class_elements'                => true,
        'ordered_imports'                       => true,
        'php_unit_strict'                       => true,
        'phpdoc_add_missing_param_annotation'   => true,
        'phpdoc_order'                          => true,
        'psr4'                                  => true,
        'strict_comparison'                     => false,
        'strict_param'                          => true,
        'binary_operator_spaces'                => ['align_double_arrow' => true, 'align_equals' => true],
        'concat_space'                          => ['spacing' => 'one'],
        'no_empty_statement'                    => true,
        'simplified_null_return'                => true,
        'no_extra_consecutive_blank_lines'      => true,
        'pre_increment'                         => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('vendor')
            ->in(__DIR__)
    )
    ->setUsingCache(false)
;
