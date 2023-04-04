<?php

declare(strict_types=1);

/**
 * Contains the ProductSearchTest.php class.
 *
 * @copyright   Copyright (c) 2023 Attila Fulop
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-04-03
 *
 */

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Konekt\Search\Searcher;
use Vanilo\Foundation\Models\MasterProduct;
use Vanilo\Foundation\Models\Product;
use Vanilo\Foundation\Models\Taxon;
use Vanilo\Foundation\Search\ProductSearch;
use Vanilo\Foundation\Tests\TestCase;
use Vanilo\Product\Models\ProductState;
use Vanilo\Properties\Models\Property;
use Vanilo\Properties\Models\PropertyValue;

class ProductSearchTest extends TestCase
{
    protected function setUp(): void
    {
        TestCase::setUp();
        MasterProduct::query()->delete();
        Product::query()->delete();
        Property::query()->delete();
    }

    /** @test */
    public function it_excludes_inactive_products_by_default()
    {
        factory(Product::class, 11)->create([
            'state' => ProductState::ACTIVE
        ]);
        factory(Product::class, 3)->create([
            'state' => ProductState::INACTIVE
        ]);
        factory(Product::class, 1)->create([
            'state' => ProductState::DRAFT
        ]);
        factory(Product::class, 2)->create([
            'state' => ProductState::RETIRED
        ]);
        factory(Product::class, 2)->create([
            'state' => ProductState::UNAVAILABLE
        ]);

        $search = new ProductSearch();
        $this->assertCount(11, $search->getResults());
    }

    /** @test */
    public function inactive_products_can_be_included()
    {
        factory(Product::class, 7)->create([
            'state' => ProductState::ACTIVE
        ]);
        factory(Product::class, 1)->create([
            'state' => ProductState::INACTIVE
        ]);
        factory(Product::class, 3)->create([
            'state' => ProductState::DRAFT
        ]);
        factory(Product::class, 2)->create([
            'state' => ProductState::RETIRED
        ]);
        factory(Product::class, 4)->create([
            'state' => ProductState::UNAVAILABLE
        ]);

        $finder = new ProductSearch();
        $this->assertCount(17, $finder->withInactiveProducts()->getResults());
    }

    /** @test */
    public function it_finds_a_product_by_exact_name()
    {
        factory(Product::class, 83)->create();
        factory(Product::class)->create([
            'name' => 'Shiny Glue'
        ]);

        $finder = new ProductSearch();
        $result = $finder->nameContains('Shiny Glue')->getResults();

        $this->assertCount(1, $result);

        $first = $result->first();
        $this->assertInstanceOf(Product::class, $first);
        $this->assertEquals('Shiny Glue', $first->name);
    }

    /** @test */
    public function it_finds_a_product_where_name_begins_with()
    {
        factory(Product::class, 35)->create();
        factory(Product::class)->create([
            'name' => 'Matured Cheese'
        ]);

        $finder = new ProductSearch();
        $result = $finder->nameStartsWith('Mature')->getResults();

        $this->assertCount(1, $result);

        $first = $result->first();
        $this->assertInstanceOf(Product::class, $first);
        $this->assertEquals('Matured Cheese', $first->name);
    }

    /** @test */
    public function it_finds_both_products_and_master_products_where_name_begins_with()
    {
        factory(Product::class, 9)->create();
        factory(Product::class)->create(['name' => 'Matured Cheese']);
        factory(MasterProduct::class)->create(['name' => 'Mature People']);

        $finder = new ProductSearch();
        $result = $finder->nameStartsWith('Mature')->getResults();

        $this->assertCount(2, $result);

        $first = $result->first();
        $this->assertEquals($first instanceof MasterProduct ? 'Mature People': 'Matured Cheese', $first->name);

        $second = $result->last();
        $this->assertEquals($second instanceof MasterProduct ? 'Mature People': 'Matured Cheese', $second->name);
    }

    /** @test */
    public function it_finds_a_product_where_name_ends_with()
    {
        factory(Product::class, 27)->create();
        factory(Product::class)->create([
            'name' => 'Bobinated Transformator'
        ]);

        $finder = new ProductSearch();
        $result = $finder->nameEndsWith('Transformator')->getResults();

        $this->assertCount(1, $result);

        $first = $result->first();
        $this->assertInstanceOf(Product::class, $first);
        $this->assertEquals('Bobinated Transformator', $first->name);
    }

    /** @test */
    public function it_finds_multiple_results_where_name_contains_search_term()
    {
        factory(Product::class, 11)->create();
        factory(Product::class)->create(['name' => 'Mandarin As Language']);
        factory(MasterProduct::class)->create(['name' => 'Crazy Mandarins']);
        factory(Product::class)->create(['name' => 'Mandarin']);
        factory(MasterProduct::class)->create(['name' => 'Mandarinic Fruits']);

        $finder = new ProductSearch();
        $this->assertCount(4, $finder->nameContains('Mandarin')->getResults());
    }

    /** @test */
    public function it_finds_multiple_results_where_name_starts_with_search_term()
    {
        factory(Product::class, 18)->create();
        factory(Product::class)->create(['name' => 'Orange Is Good']);
        factory(Product::class)->create(['name' => 'This Should Not Be Found']);
        factory(Product::class)->create(['name' => 'Oranges From Morocco']);

        $finder = new ProductSearch();
        $this->assertCount(2, $finder->nameStartsWith('Orange')->getResults());
    }

    /** @test */
    public function it_finds_multiple_results_where_name_ends_with_search_term()
    {
        factory(Product::class, 7)->create();
        factory(Product::class)->create(['name' => 'Awesome Blueberries']);
        factory(Product::class)->create(['name' => 'Blueberries Not Here']);
        factory(Product::class)->create(['name' => 'Blueberries']);
        factory(Product::class)->create(['name' => 'Vanilla + Blueberries']);

        $finder = new ProductSearch();
        $this->assertCount(3, $finder->nameEndsWith('Blueberries')->getResults());
    }

    /** @test */
    public function name_based_finders_can_be_combined()
    {
        factory(Product::class, 21)->create();
        factory(Product::class)->create(['name' => 'Waka Time']);
        factory(Product::class)->create(['name' => 'Kaka Waka']);
        factory(Product::class)->create(['name' => 'Tugo Waka Batagang']);

        $finder = new ProductSearch();
        $result = $finder
                    ->nameEndsWith('Waka')
                    ->orNameStartsWith('Waka')
                    ->getResults();

        $this->assertCount(2, $result);
    }

    /** @test */
    public function returns_products_based_on_a_single_taxon()
    {
        // Products without taxons
        factory(Product::class, 20)->create();

        // Products within taxon 1
        $taxon1 = factory(Taxon::class)->create();
        factory(Product::class, 7)->create()->each(function (Product $product) use ($taxon1) {
            $product->addTaxon($taxon1);
        });

        // Products within taxon 2
        $taxon2 = factory(Taxon::class)->create();
        factory(Product::class, 3)->create()->each(function (Product $product) use ($taxon2) {
            $product->addTaxon($taxon2);
        });

        $this->assertCount(7, (new ProductSearch())->withinTaxon($taxon1)->getResults());
        $this->assertCount(3, (new ProductSearch())->withinTaxon($taxon2)->getResults());
    }

    /** @test */
    public function returns_products_based_on_two_taxons_set_in_two_consecutive_calls()
    {
        // Products without taxons
        factory(Product::class, 20)->create();

        // Products within taxon 1
        $taxon1 = factory(Taxon::class)->create();
        factory(Product::class, 4)->create()->each(function (Product $product) use ($taxon1) {
            $product->addTaxon($taxon1);
        });

        // Products within taxon 2
        $taxon2 = factory(Taxon::class)->create();
        factory(Product::class, 2)->create()->each(function (Product $product) use ($taxon2) {
            $product->addTaxon($taxon2);
        });

        $finder = new ProductSearch();
        $finder->withinTaxon($taxon1)->orWithinTaxon($taxon2);
        $this->assertCount(6, $finder->getResults());
    }

    /** @test */
    public function returns_products_based_on_several_taxons()
    {
        // Products without taxons
        factory(Product::class, 10)->create();

        $taxon1 = factory(Taxon::class)->create();
        factory(Product::class, 11)->create()->each(function (Product $product) use ($taxon1) {
            $product->addTaxons([$taxon1]);
        });

        $taxon2 = factory(Taxon::class)->create();
        factory(Product::class, 5)->create()->each(function (Product $product) use ($taxon2) {
            $product->addTaxon($taxon2);
        });

        $this->assertCount(16, (new ProductSearch())->withinTaxons([$taxon1, $taxon2])->getResults());
    }

    /** @test */
    public function returns_products_based_on_several_taxons_set_in_consecutive_calls()
    {
        // Products without taxons
        factory(Product::class, 10)->create();

        $taxon1 = factory(Taxon::class)->create();
        factory(Product::class, 4)->create()->each(function (Product $product) use ($taxon1) {
            $product->addTaxons([$taxon1]);
        });

        $taxon2 = factory(Taxon::class)->create();
        factory(Product::class, 8)->create()->each(function (Product $product) use ($taxon2) {
            $product->addTaxon($taxon2);
        });

        $finder = new ProductSearch();
        $finder->withinTaxons([$taxon1])->orWithinTaxons([$taxon2]);
        $this->assertCount(12, $finder->getResults());
    }

    /** @test */
    public function returns_products_based_on_a_single_property_value()
    {
        // Background products without attributes
        factory(Product::class, 10)->create();

        $red = factory(PropertyValue::class)->create([
            'value' => 'red',
            'title' => 'Red'
        ]);

        factory(Product::class, 9)->create()->each(function (Product $product) use ($red) {
            $product->addPropertyValue($red);
        });

        $finder = new ProductSearch();
        $finder->havingPropertyValue($red);
        $this->assertCount(9, $finder->getResults());
    }

    /** @test */
    public function returns_products_based_on_a_single_property_name_and_several_value_names()
    {
        // Background products without attributes
        factory(Product::class, 10)->create();

        $property = factory(Property::class)->create([
            'name' => 'Wheel Size',
            'slug' => 'wheel'
        ]);

        $twentyseven = factory(PropertyValue::class)->create([
            'value' => '27',
            'title' => '27"',
            'property_id' => $property
        ]);

        $twentynine = factory(PropertyValue::class)->create([
            'value' => '29',
            'title' => '29"',
            'property_id' => $property
        ]);

        factory(Product::class, 8)->create()->each(function (Product $product) use ($twentyseven) {
            $product->addPropertyValue($twentyseven);
        });

        factory(Product::class, 19)->create()->each(function (Product $product) use ($twentynine) {
            $product->addPropertyValue($twentynine);
        });

        $finder = new ProductSearch();
        $finder->havingPropertyValuesByName('wheel', ['27','29']);
        $this->assertCount(27, $finder->getResults());
    }

    /** @test */
    public function returns_products_based_on_several_property_values()
    {
        // Background products without attributes
        factory(Product::class, 25)->create();

        $value1 = factory(PropertyValue::class)->create();
        $value2 = factory(PropertyValue::class)->create();

        factory(Product::class, 13)->create()->each(function (Product $product) use ($value1) {
            $product->addPropertyValue($value1);
        });

        factory(Product::class, 2)->create()->each(function (Product $product) use ($value2) {
            $product->addPropertyValue($value2);
        });

        $finder = new ProductSearch();
        $finder->havingPropertyValues([$value1, $value2]);
        $this->assertCount(15, $finder->getResults());
    }

    /** @test */
    public function returns_products_based_on_property_values_and_on_taxons()
    {
        // Products without taxons
        factory(Product::class, 90)->create();

        $taxon = factory(Taxon::class)->create();
        factory(Product::class, 45)->create()->each(function (Product $product) use ($taxon) {
            $product->addTaxon($taxon);
        });

        $propertyValue = factory(PropertyValue::class)->create();
        factory(Product::class, 19)->create()->each(function (Product $product) use ($propertyValue) {
            $product->addPropertyValue($propertyValue);
        });

        $finder = new ProductSearch();
        $finder->withinTaxon($taxon)->orHavingPropertyValue($propertyValue);
        $this->assertCount(64, $finder->getResults());
    }

    /** @test */
    public function returns_products_based_on_property_values_and_on_taxons_with_search_terms()
    {
        // Products without taxons
        factory(Product::class, 37)->create();

        $taxon = factory(Taxon::class)->create();
        factory(Product::class, 19)->create()->each(function (Product $product) use ($taxon) {
            $product->addTaxon($taxon);
        });
        factory(Product::class, 4)->create([
            'name' => 'NER Posvany'
        ])->each(function (Product $product) use ($taxon) {
            $product->addTaxon($taxon);
        });

        $propertyValue = factory(PropertyValue::class)->create();
        factory(Product::class, 7)->create()->each(function (Product $product) use ($propertyValue) {
            $product->addPropertyValue($propertyValue);
        });
        factory(Product::class, 6)->create([
            'name' => 'Phillip NER'
        ])->each(function (Product $product) use ($propertyValue) {
            $product->addPropertyValue($propertyValue);
        });

        factory(Product::class, 11)->create([
            'name' => 'Phillip NER'
        ])->each(function (Product $product) use ($propertyValue, $taxon) {
            $product->addTaxon($taxon);
            $product->addPropertyValue($propertyValue);
        });

        $finder = new ProductSearch();
        $finder->withinTaxon($taxon)->havingPropertyValue($propertyValue)->nameContains('NER');
        $this->assertCount(11, $finder->getResults());
    }

    /** @test */
    public function it_returns_searcher()
    {
        $search = new ProductSearch();
        $this->assertInstanceOf(Searcher::class, $search->getSearcher());
    }

    /** @test */
    public function it_can_simple_paginate()
    {
        factory(Product::class, 15)->create();

        $finder = new ProductSearch();
        $results = $finder->simplePaginate(8);

        $this->assertInstanceOf(Paginator::class, $results);
        $this->assertCount(8, $results->items());
    }

    /** @test */
    public function it_can_paginate()
    {
        factory(Product::class, 15)->create();

        $finder = new ProductSearch();
        $results = $finder->paginate(8);

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
        $this->assertCount(8, $results->items());
    }
}
