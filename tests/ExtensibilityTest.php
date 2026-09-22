<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Core\Dictionary;
use Blaspsoft\Blasp\Core\Normalizers\HomoglyphNormalizer;
use Blaspsoft\Blasp\Facades\Blasp;
use Illuminate\Support\Facades\Config;

/**
 * The knobs an installed app can turn without copying package files:
 * homoglyph folding, phrase entries and the substitution table.
 */
class ExtensibilityTest extends TestCase
{
    // --- homoglyphs -------------------------------------------------------

    public function test_cyrillic_and_greek_lookalikes_are_matched(): void
    {
        // Cyrillic с and Greek υ
        $this->assertTrue(Blasp::check('fuсk')->isOffensive());
        $this->assertTrue(Blasp::check('fυck')->isOffensive());
    }

    public function test_fullwidth_letters_are_matched(): void
    {
        $this->assertTrue(Blasp::check('ｆｕｃｋ')->isOffensive());
    }

    public function test_accented_letters_missing_from_the_substitution_table_are_matched(): void
    {
        $this->assertNotContains('ā', config('blasp.substitutions')['/a/']);
        $this->assertTrue(Blasp::check('dāmn')->isOffensive());
    }

    public function test_the_original_text_is_masked_not_the_folded_form(): void
    {
        $result = Blasp::check('Café fuсk');

        $this->assertSame('Café ****', $result->clean());
        $this->assertSame('fuсk', $result->words()->first()->text);
    }

    public function test_homoglyph_folding_can_be_disabled(): void
    {
        Config::set('blasp.homoglyphs', false);

        $this->assertFalse(Blasp::check('fuсk')->isOffensive());
        $this->assertTrue(Blasp::check('fuck')->isOffensive());
    }

    public function test_folding_preserves_length_and_leaves_other_scripts_alone(): void
    {
        foreach (['fuсk', 'ｆｕｃｋ', 'gānjā', 'Café', 'ﬁne', 'straße', 'मादरचोद', 'こんにちは'] as $input) {
            $this->assertSame(mb_strlen($input), mb_strlen(HomoglyphNormalizer::fold($input)), $input);
        }

        $this->assertSame('मादरचोद', HomoglyphNormalizer::fold('मादरचोद'));
        $this->assertSame('ﬁne', HomoglyphNormalizer::fold('ﬁne'));
        $this->assertSame('ganja', HomoglyphNormalizer::fold('gānjā'));
    }

    // --- phrases ----------------------------------------------------------

    public function test_a_phrase_entry_matches_across_any_separator(): void
    {
        $check = Blasp::block('sieg heil');

        foreach (['sieg heil', 'sieg-heil', 'siegheil', 'sieg_heil', 's-i-e-g heil'] as $text) {
            $this->assertTrue($check->check($text)->isOffensive(), $text);
        }

        $this->assertSame('*********', $check->check('sieg heil')->clean());
        $this->assertSame('********', $check->check('siegheil')->clean());
    }

    public function test_shipped_phrase_entries_match_hyphenated(): void
    {
        $this->assertTrue(Blasp::check('white-power')->isOffensive());
        $this->assertTrue(Blasp::check('whitepower')->isOffensive());
    }

    // --- substitutions ----------------------------------------------------

    public function test_substitutions_append_are_merged_into_the_table(): void
    {
        $this->assertFalse(Blasp::check('fvck')->isOffensive());

        Config::set('blasp.substitutions_append', ['/u/' => ['v']]);

        $this->assertTrue(Blasp::check('fvck')->isOffensive());
        $this->assertContains('u', Dictionary::forLanguage('english')->getSubstitutions()['/u/']);
        $this->assertContains('v', Dictionary::forLanguage('english')->getSubstitutions()['/u/']);
    }

    public function test_merge_substitutions_adds_per_letter_without_duplicates(): void
    {
        $merged = Dictionary::mergeSubstitutions(
            ['/a/' => ['a', '4'], '/b/' => ['b']],
            ['/a/' => ['4', '@'], '/c/' => ['c', '('], '/d/' => 'not-a-list']
        );

        $this->assertSame(['a', '4', '@'], $merged['/a/']);
        $this->assertSame(['b'], $merged['/b/']);
        $this->assertSame(['c', '('], $merged['/c/']);
        $this->assertArrayNotHasKey('/d/', $merged);
    }

    public function test_default_table_covers_common_digit_leetspeak(): void
    {
        foreach (['s1ut', 'cun7', 'sh17', 'j122'] as $text) {
            $this->assertTrue(Blasp::check($text)->isOffensive(), $text);
        }
    }
}
