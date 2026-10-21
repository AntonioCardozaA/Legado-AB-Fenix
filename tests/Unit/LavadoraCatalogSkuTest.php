<?php

namespace Tests\Unit;

use App\Support\LavadoraCatalog;
use PHPUnit\Framework\TestCase;

class LavadoraCatalogSkuTest extends TestCase
{
    public function test_returns_catarina_sku_by_chain_pitch(): void
    {
        $this->assertSame(125, LavadoraCatalog::pasoCadenaLinea('L-08'));
        $this->assertSame('4064265', LavadoraCatalog::skuComponente('L-08', 'CATARINAS'));

        $this->assertSame(140, LavadoraCatalog::pasoCadenaLinea('L-12'));
        $this->assertSame('4065310', LavadoraCatalog::skuComponente('L-12', 'CATARINAS'));

        $this->assertSame(173, LavadoraCatalog::pasoCadenaLinea('L-07'));
        $this->assertSame('4094364', LavadoraCatalog::skuComponente('L-07', 'CATARINAS'));
    }

    public function test_returns_guide_sku_from_pitch_aware_map(): void
    {
        $this->assertSame('4066462', LavadoraCatalog::skuComponente('L-08', 'GUI_INF_TANQUE'));
        $this->assertSame('4066460', LavadoraCatalog::skuComponente('L-12', 'GUI_INT_TANQUE'));
        $this->assertSame('4066459', LavadoraCatalog::skuComponente('L-07', 'GUI_SUP_TANQUE'));
    }

    public function test_keeps_rv250_same_sku_across_lines(): void
    {
        $this->assertSame('4067643', LavadoraCatalog::skuComponente('L-05', 'RV200'));
        $this->assertSame('4067643', LavadoraCatalog::skuComponente('L-12', 'RV200_SIN_FIN'));
        $this->assertSame(['4067643'], LavadoraCatalog::skusComponente('L-05', 'RV200'));
    }

    public function test_builds_single_sku_summary(): void
    {
        $summary = LavadoraCatalog::resumenSkuComponente('L-04', 'BUJE_ESPIGA');

        $this->assertSame('SKU: 4017810', $summary['label']);
        $this->assertSame('4017810', $summary['sku']);
    }

    public function test_assigns_rv250_only_to_reductor_two_on_target_lines(): void
    {
        foreach (['L-05', 'L-12', 'L-13'] as $linea) {
            $this->assertSame('RV200', LavadoraCatalog::modeloReductorSinFinCorona($linea, 'Reductor 1'));
            $this->assertSame('RV250', LavadoraCatalog::modeloReductorSinFinCorona($linea, 'Reductor 2'));
            $this->assertSame('RV200', LavadoraCatalog::modeloReductorSinFinCorona($linea, 'Reductor 3'));
        }
    }

    public function test_uses_rv200_outside_target_lines(): void
    {
        $this->assertSame('RV200', LavadoraCatalog::modeloReductorSinFinCorona('L-04', 'Reductor 2'));
    }

    public function test_exposes_a_uniform_visible_name_for_both_models(): void
    {
        $this->assertSame(
            LavadoraCatalog::REDUCTOR_SIN_FIN_CORONA_VISIBLE,
            LavadoraCatalog::nombreVisibleComponente('RV200 Sin Fin Corona', 'L05_REDUCTOR_1_RV200')
        );
        $this->assertSame(
            LavadoraCatalog::REDUCTOR_SIN_FIN_CORONA_VISIBLE,
            LavadoraCatalog::nombreVisibleComponente('RV250 Sin Fin Corona', 'L05_REDUCTOR_2_RV200')
        );
    }
}
