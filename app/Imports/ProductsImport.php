<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\CurrentStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PublicProduct;
use App\Models\PublicProductCategory;
use App\Models\SubCategory;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;

class ProductsImport implements SkipsOnFailure, ToModel, WithEvents, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    protected array $categoriesCache = [];
    protected array $subCategoriesCache = [];
    protected array $importedItems = [];
    protected int $importedCount = 0;

    public function __construct()
    {
        $this->refreshCategoriesCache();
    }

    protected function refreshCategoriesCache(): void
    {
        $this->categoriesCache = Category::pluck('id', 'name')->toArray();
        $this->subCategoriesCache = [];
        foreach (SubCategory::all() as $sub) {
            $catId = $sub->category_id;
            $this->subCategoriesCache[$catId][mb_strtolower(trim($sub->name))] = $sub->id;
        }
    }

    public function getImportedRowCount(): int
    {
        return $this->importedCount;
    }

    /**
     * Map each row from Excel
     */
    public function model(array $row): ?Product
    {
        $norm = $this->normalizeRow($row);

        $title = $norm['title'] ?? null;
        if (empty($title)) {
            return null;
        }

        $id = !empty($norm['id']) ? (int) $norm['id'] : null;
        $sku = !empty($norm['sku']) ? trim((string) $norm['sku']) : null;
        $categoryRaw = (string) ($norm['category'] ?? '');
        $subCategoryRaw = (string) ($norm['sub_category'] ?? '');

        $categoryList = $this->parseMultiValue($categoryRaw);
        $subCategoryList = $this->parseMultiValue($subCategoryRaw);

        // Find existing product:
        // 1. By ID (if specified and valid)
        // 2. By SKU (if specified and not empty)
        // 3. By Title (fallback)
        $product = null;

        if ($id) {
            $product = Product::withTrashed()->find($id);
            if ($product && $product->trashed()) {
                $product->restore();
            }
        }

        if (!$product && $sku) {
            $product = Product::where('sku', $sku)->withTrashed()->first();
            if ($product && $product->trashed()) {
                $product->restore();
            }
        }

        if (!$product) {
            $product = Product::where('title', $title)->withTrashed()->first();
            if ($product && $product->trashed()) {
                $product->restore();
            }
        }

        // Prepare product data array with all columns
        $productData = [
            'sku'                             => $sku,
            'module'                          => $norm['module'] ?? 'A',
            'image'                           => $norm['image'] ?? null,
            'category'                        => $categoryRaw ?: ($categoryList[0] ?? null),
            'sub_category'                    => $subCategoryRaw ?: ($subCategoryList[0] ?? null),
            'brand'                           => $norm['brand'] ?? null,
            'title'                           => $title,
            'subtitle'                        => $norm['subtitle'] ?? null,
            'descriptions'                    => $norm['descriptions'] ?? null,
            'stock'                           => $this->toInt($norm['stock'] ?? 0),
            'minimum_stock'                   => $this->toInt($norm['minimum_stock'] ?? 0),
            'price'                           => $this->toFloat($norm['price'] ?? 0),
            'is_asset'                        => $this->parseBool($norm['is_asset'] ?? false),
            'warranty_period_months'          => $this->toInt($norm['warranty_period_months'] ?? 12),
            // Wireless
            'wireless_standard'               => $norm['wireless_standard'] ?? null,
            'wireless_stream'                 => $norm['wireless_stream'] ?? null,
            'wireless_stream1'                => $norm['wireless_stream1'] ?? null,
            'wireless_stream2'                => $norm['wireless_stream2'] ?? null,
            'wireless_stream3'                => $norm['wireless_stream3'] ?? null,
            'wireless_stream4'                => $norm['wireless_stream4'] ?? null,
            'wireless_stream5'                => $norm['wireless_stream5'] ?? null,
            // Controller / Capacity
            'manageable_aps'                  => $norm['manageable_aps'] ?? null,
            'ap_number'                       => $norm['ap_number'] ?? null,
            'number_of_clients'               => $norm['number_of_clients'] ?? null,
            'capacity'                        => $norm['capacity'] ?? null,
            'ip_rating'                       => $norm['ip_rating'] ?? null,
            'switching'                       => $norm['switching'] ?? null,
            'throughput'                      => $norm['throughput'] ?? null,
            // Memory
            'flash_memory'                    => $norm['flash_memory'] ?? null,
            'sdram_memory'                    => $norm['sdram_memory'] ?? null,
            // Interfaces
            'interface_main'                  => $norm['interface_main'] ?? null,
            'interface1'                      => $norm['interface1'] ?? null,
            'interface2'                      => $norm['interface2'] ?? null,
            'interface3'                      => $norm['interface3'] ?? null,
            'interface4'                      => $norm['interface4'] ?? null,
            'interface5'                      => $norm['interface5'] ?? null,
            'antena'                          => $norm['antena'] ?? null,
            // CU
            'cu'                              => $norm['cu'] ?? null,
            'cu1'                             => $norm['cu1'] ?? null,
            'cu2'                             => $norm['cu2'] ?? null,
            'cu3'                             => $norm['cu3'] ?? null,
            'cu4'                             => $norm['cu4'] ?? null,
            'cu5'                             => $norm['cu5'] ?? null,
            // Additional interfaces
            'additional_interface1'           => $norm['additional_interface1'] ?? null,
            'additional_interface2'           => $norm['additional_interface2'] ?? null,
            'additional_interface3'           => $norm['additional_interface3'] ?? null,
            'additional_interface4'           => $norm['additional_interface4'] ?? null,
            'additional_interface5'           => $norm['additional_interface5'] ?? null,
            // Networking
            'mac_address'                     => $norm['mac_address'] ?? null,
            'routing_table'                   => $norm['routing_table'] ?? null,
            // Environmental
            'dustproof_waterproof'            => $norm['dustproof_waterproof'] ?? null,
            'noise'                           => $norm['noise'] ?? null,
            'mtbf'                            => $norm['mtbf'] ?? null,
            'operating_temperature'           => $norm['operating_temperature'] ?? null,
            'storage_temperature'             => $norm['storage_temperature'] ?? null,
            'operating_humidity'              => $norm['operating_humidity'] ?? null,
            // Power
            'power1'                          => $norm['power1'] ?? null,
            'power2'                          => $norm['power2'] ?? null,
            'power3'                          => $norm['power3'] ?? null,
            'power4'                          => $norm['power4'] ?? null,
            'power5'                          => $norm['power5'] ?? null,
            'power_consumptions'              => $norm['power_consumptions'] ?? null,
            'power_consumption_with_poe'      => $norm['power_consumption_with_poe'] ?? null,
            'power_consumption_excluding_poe' => $norm['power_consumption_excluding_poe'] ?? null,
            'poe_budget'                      => $norm['poe_budget'] ?? null,
            // Physical & Diagram
            'dimensions'                      => $norm['dimensions'] ?? null,
            'diagram'                         => $norm['diagram'] ?? null,
            'network_diagram'                 => $norm['network_diagram'] ?? null,
        ];

        // Spec 1 to 20
        for ($i = 1; $i <= 20; $i++) {
            $productData["spec{$i}"] = $norm["spec{$i}"] ?? null;
        }

        // Fitur 1 to 20
        for ($i = 1; $i <= 20; $i++) {
            $productData["fitur{$i}"] = $norm["fitur{$i}"] ?? null;
        }

        // Save / update Product
        if ($product) {
            $product->update($productData);
        } else {
            if ($id) {
                $productData['id'] = $id;
            }
            $product = Product::create($productData);
        }

        // Sync stock to default warehouse
        $defaultWarehouse = Warehouse::getDefault();
        if ($defaultWarehouse) {
            CurrentStock::updateOrCreate(
                [
                    'product_id'   => $product->id,
                    'warehouse_id' => $defaultWarehouse->id,
                ],
                [
                    'quantity'     => $product->stock ?? 0,
                    'last_updated' => now(),
                ]
            );
        }

        // Sync categories to product_category pivot
        $categoryPairs = $this->resolveCategoryPairs($categoryList, $subCategoryList);
        $this->syncProductCategories($product, $categoryPairs);

        // Sync to PublicProduct
        $publicProduct = $this->syncToPublicProduct($product, $norm, $categoryPairs);

        $this->importedItems[] = [
            'product_id'        => $product->id,
            'public_product_id' => $publicProduct?->id,
        ];
        $this->importedCount++;

        return $product;
    }

    /**
     * Resolve category & subcategory name pairs and ensure they exist in DB
     */
    protected function resolveCategoryPairs(array $categories, array $subCategories): array
    {
        $pairs = [];
        $max = max(count($categories), count($subCategories), 1);

        for ($i = 0; $i < $max; $i++) {
            $catName = $categories[$i] ?? ($categories[0] ?? null);
            $subName = $subCategories[$i] ?? null;

            if (!$catName) {
                continue;
            }

            // Find or create Category
            $catId = $this->findOrCreateCategory($catName);

            // Find or create SubCategory if subName present
            $subId = null;
            if ($subName && $catId) {
                $subId = $this->findOrCreateSubCategory($catId, $subName);
            }

            if ($catId) {
                $pairs[] = [
                    'category_id'     => $catId,
                    'sub_category_id' => $subId,
                    'category_name'   => $catName,
                    'sub_category_name' => $subName,
                ];
            }
        }

        return $pairs;
    }

    protected function findOrCreateCategory(string $name): int
    {
        $trimmed = trim($name);
        foreach ($this->categoriesCache as $cName => $cId) {
            if (mb_strtolower($cName) === mb_strtolower($trimmed)) {
                return $cId;
            }
        }

        $category = Category::firstOrCreate(['name' => $trimmed]);
        $this->categoriesCache[$category->name] = $category->id;
        return $category->id;
    }

    protected function findOrCreateSubCategory(int $categoryId, string $name): int
    {
        $trimmed = trim($name);
        $lower = mb_strtolower($trimmed);

        if (isset($this->subCategoriesCache[$categoryId][$lower])) {
            return $this->subCategoriesCache[$categoryId][$lower];
        }

        $sub = SubCategory::firstOrCreate([
            'category_id' => $categoryId,
            'name'        => $trimmed,
        ]);

        $this->subCategoriesCache[$categoryId][$lower] = $sub->id;
        return $sub->id;
    }

    protected function syncProductCategories(Product $product, array $pairs): void
    {
        ProductCategory::where('product_id', $product->id)->delete();

        foreach ($pairs as $pair) {
            ProductCategory::create([
                'product_id'      => $product->id,
                'category_id'     => $pair['category_id'],
                'sub_category_id' => $pair['sub_category_id'],
            ]);
        }
    }

    /**
     * Create or update PublicProduct and sync its categories
     */
    protected function syncToPublicProduct(Product $product, array $norm, array $categoryPairs): ?PublicProduct
    {
        $publicProduct = null;

        if ($product->public_product_id) {
            $publicProduct = PublicProduct::find($product->public_product_id);
        }

        if (!$publicProduct && !empty($product->sku)) {
            $publicProduct = PublicProduct::where('sku', $product->sku)->first();
        }

        if (!$publicProduct && !empty($product->title)) {
            $publicProduct = PublicProduct::where('title', $product->title)->first();
        }

        // Generate or retain slug
        $slug = !empty($norm['slug']) ? trim($norm['slug']) : ($publicProduct?->slug ?? Str::slug($product->title));
        if (empty($slug)) {
            $slug = 'product-' . $product->id;
        }

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (PublicProduct::where('slug', $slug)->where('id', '!=', $publicProduct?->id ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $publicData = [
            'sku'                             => $product->sku,
            'module'                          => $product->module ?? 'A',
            'slug'                            => $slug,
            'image'                           => $product->image,
            'category'                        => $product->category,
            'sub_category'                    => $product->sub_category,
            'brand'                           => $product->brand,
            'stock'                           => $product->stock ?? 0,
            'title'                           => $product->title,
            'subtitle'                        => $product->subtitle,
            'descriptions'                    => $product->descriptions,
            'wireless_standard'               => $product->wireless_standard,
            'wireless_stream'                 => $product->wireless_stream,
            'wireless_stream1'                => $product->wireless_stream1,
            'wireless_stream2'                => $product->wireless_stream2,
            'wireless_stream3'                => $product->wireless_stream3,
            'wireless_stream4'                => $product->wireless_stream4,
            'wireless_stream5'                => $product->wireless_stream5,
            'manageable_aps'                  => $product->manageable_aps,
            'ap_number'                       => $product->ap_number,
            'number_of_clients'               => $product->number_of_clients,
            'capacity'                        => $product->capacity,
            'ip_rating'                       => $product->ip_rating,
            'switching'                       => $product->switching,
            'throughput'                      => $product->throughput,
            'flash_memory'                    => $product->flash_memory,
            'sdram_memory'                    => $product->sdram_memory,
            'interface_main'                  => $product->interface_main,
            'interface1'                      => $product->interface1,
            'interface2'                      => $product->interface2,
            'interface3'                      => $product->interface3,
            'interface4'                      => $product->interface4,
            'interface5'                      => $product->interface5,
            'antena'                          => $product->antena,
            'cu'                              => $product->cu,
            'cu1'                             => $product->cu1,
            'cu2'                             => $product->cu2,
            'cu3'                             => $product->cu3,
            'cu4'                             => $product->cu4,
            'cu5'                             => $product->cu5,
            'additional_interface1'           => $product->additional_interface1,
            'additional_interface2'           => $product->additional_interface2,
            'additional_interface3'           => $product->additional_interface3,
            'additional_interface4'           => $product->additional_interface4,
            'additional_interface5'           => $product->additional_interface5,
            'mac_address'                     => $product->mac_address,
            'routing_table'                   => $product->routing_table,
            'dustproof_waterproof'            => $product->dustproof_waterproof,
            'noise'                           => $product->noise,
            'mtbf'                            => $product->mtbf,
            'operating_temperature'           => $product->operating_temperature,
            'storage_temperature'             => $product->storage_temperature,
            'operating_humidity'              => $product->operating_humidity,
            'power1'                          => $product->power1,
            'power2'                          => $product->power2,
            'power3'                          => $product->power3,
            'power4'                          => $product->power4,
            'power5'                          => $product->power5,
            'power_consumptions'              => $product->power_consumptions,
            'power_consumption_with_poe'      => $product->power_consumption_with_poe,
            'power_consumption_excluding_poe' => $product->power_consumption_excluding_poe,
            'poe_budget'                      => $product->poe_budget,
            'dimensions'                      => $product->dimensions,
            'diagram'                         => $product->diagram,
            'network_diagram'                 => $product->network_diagram,
            'is_active'                       => true,
            'sort_order'                      => (int) ($norm['sort_order'] ?? ($publicProduct?->sort_order ?? 0)),
        ];

        for ($i = 1; $i <= 20; $i++) {
            $publicData["spec{$i}"] = $product->{"spec{$i}"};
            $publicData["fitur{$i}"] = $product->{"fitur{$i}"};
        }

        if ($publicProduct) {
            $publicProduct->update($publicData);
        } else {
            $publicProduct = PublicProduct::create($publicData);
        }

        // Link back to product
        $product->update(['public_product_id' => $publicProduct->id]);

        // Sync public_product_category pivot
        PublicProductCategory::where('public_product_id', $publicProduct->id)->delete();
        foreach ($categoryPairs as $pair) {
            PublicProductCategory::create([
                'public_product_id' => $publicProduct->id,
                'category_id'       => $pair['category_id'],
                'sub_category_id'   => $pair['sub_category_id'],
            ]);
        }

        return $publicProduct;
    }

    /**
     * Normalize Excel row keys to snake_case and match synonyms
     */
    protected function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $rawKey = trim((string) $key);
            $cleanKey = mb_strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawKey));

            if (is_string($value)) {
                $value = trim($value);
                $value = $value === '' ? null : $value;
            }

            // Map aliases to database snake_case field names
            $mappedKey = match ($cleanKey) {
                'id' => 'id',
                'module' => 'module',
                'sku' => 'sku',
                'image' => 'image',
                'title' => 'title',
                'subtitle' => 'subtitle',
                'category' => 'category',
                'subcategory' => 'sub_category',
                'brand' => 'brand',
                'stock' => 'stock',
                'minimumstock' => 'minimum_stock',
                'price' => 'price',
                'isasset' => 'is_asset',
                'warrantyperiodmonths', 'warrantyperiod' => 'warranty_period_months',
                'descriptions', 'description' => 'descriptions',
                'wirelessstandard' => 'wireless_standard',
                'wirelessstream' => 'wireless_stream',
                'wirelessstream1' => 'wireless_stream1',
                'wirelessstream2' => 'wireless_stream2',
                'wirelessstream3' => 'wireless_stream3',
                'wirelessstream4' => 'wireless_stream4',
                'wirelessstream5' => 'wireless_stream5',
                'manageableaps' => 'manageable_aps',
                'apnumber' => 'ap_number',
                'numberofclients' => 'number_of_clients',
                'capacity' => 'capacity',
                'iprating' => 'ip_rating',
                'switching' => 'switching',
                'throughput' => 'throughput',
                'flashmemory' => 'flash_memory',
                'sdrammemory' => 'sdram_memory',
                'interface', 'interfacemain' => 'interface_main',
                'interface1' => 'interface1',
                'interface2' => 'interface2',
                'interface3' => 'interface3',
                'interface4' => 'interface4',
                'interface5' => 'interface5',
                'antena', 'antenna' => 'antena',
                'cu' => 'cu',
                'cu1' => 'cu1',
                'cu2' => 'cu2',
                'cu3' => 'cu3',
                'cu4' => 'cu4',
                'cu5' => 'cu5',
                'aditionalinterface1', 'additionalinterface1' => 'additional_interface1',
                'aditionalinterface2', 'additionalinterface2' => 'additional_interface2',
                'aditionalinterface3', 'additionalinterface3' => 'additional_interface3',
                'aditionalinterface4', 'additionalinterface4' => 'additional_interface4',
                'aditionalinterface5', 'additionalinterface5' => 'additional_interface5',
                'macaddress' => 'mac_address',
                'routingtable' => 'routing_table',
                'dustproofwaterproof' => 'dustproof_waterproof',
                'noise' => 'noise',
                'mtbf' => 'mtbf',
                'operatingtemperature' => 'operating_temperature',
                'storagetemperature' => 'storage_temperature',
                'operatinghumidity' => 'operating_humidity',
                'power1' => 'power1',
                'power2' => 'power2',
                'power3' => 'power3',
                'power4' => 'power4',
                'power5' => 'power5',
                'powercomsumptions', 'powerconsumption', 'powerconsumptions' => 'power_consumptions',
                'powerconsumptionwithpoe' => 'power_consumption_with_poe',
                'powerconsumptionexcludingpoe' => 'power_consumption_excluding_poe',
                'poebudget' => 'poe_budget',
                'dimensions', 'dimension' => 'dimensions',
                'diagram' => 'diagram',
                'networkdiagram' => 'network_diagram',
                'isactive' => 'is_active',
                'sortorder' => 'sort_order',
                'slug' => 'slug',
                default => Str::snake($rawKey),
            };

            // Check spec1..spec20 or fitur1..fitur20 pattern
            if (preg_match('/^spec([1-9]|1[0-9]|20)$/', $cleanKey, $m)) {
                $mappedKey = "spec{$m[1]}";
            } elseif (preg_match('/^fitur([1-9]|1[0-9]|20)$/', $cleanKey, $m) || preg_match('/^feature([1-9]|1[0-9]|20)$/', $cleanKey, $m)) {
                $mappedKey = "fitur{$m[1]}";
            }

            $normalized[$mappedKey] = $value;
        }

        return $normalized;
    }

    protected function parseMultiValue(string $value): array
    {
        if (empty(trim($value))) {
            return [];
        }

        // Split by pipe (|) or double forward-slash if pipe not used
        $delimiter = str_contains($value, '|') ? '|' : (str_contains($value, '//') ? '//' : null);

        if ($delimiter) {
            $parts = explode($delimiter, $value);
        } else {
            $parts = [$value];
        }

        return array_values(array_filter(
            array_map('trim', $parts),
            fn ($item) => $item !== ''
        ));
    }

    protected function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $str = strtolower(trim((string) $value));
        return in_array($str, ['ya', 'yes', 'true', '1', 'y', 'aktif', 'active'], true);
    }

    protected function toInt(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        return (int) $value;
    }

    protected function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        return (float) $value;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                // Done
            },
        ];
    }

    public function rules(): array
    {
        return [
            'title' => 'required',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'title.required' => 'Nama produk (title) wajib diisi.',
        ];
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function chunkSize(): int
    {
        return 50;
    }
}
