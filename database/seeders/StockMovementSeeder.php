<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        StockMovement::truncate();

        $actorId = User::query()->value('id');

        Product::with('variants')->get()->each(function (Product $product) use ($actorId) {
            if ($product->product_type->value === 'single') {
                $this->seedItem($product, null, $product->id, $actorId);
            } else {
                $product->variants->each(fn (ProductVariant $v) => $this->seedItem($v, $v->id, $product->id, $actorId));
            }
        });
    }

    private function seedItem(Product|ProductVariant $item, ?int $variantId, int $productId, ?int $actorId): void
    {
        $running = random_int(30, 120);
        $date = Carbon::now()->subDays(random_int(40, 60));

        $rows = [[
            'type' => StockMovementType::Initial->value,
            'quantity' => $running,
            'stock_after' => $running,
            'note' => 'Opening stock',
            'at' => $date->copy(),
        ]];

        foreach (range(1, random_int(3, 6)) as $ignored) {
            $date = $date->copy()->addDays(random_int(3, 12));
            [$type, $delta, $note] = $this->randomChange();

            $prev = $running;
            $running = max(0, $running + $delta);
            $applied = $running - $prev;

            if ($applied === 0) {
                continue;
            }

            $rows[] = [
                'type' => $type->value,
                'quantity' => $applied,
                'stock_after' => $running,
                'note' => $note,
                'at' => $date->copy(),
            ];
        }

        StockMovement::insert(array_map(fn (array $r) => [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'type' => $r['type'],
            'quantity' => $r['quantity'],
            'stock_after' => $r['stock_after'],
            'note' => $r['note'],
            'user_id' => $actorId,
            'created_at' => $r['at'],
            'updated_at' => $r['at'],
        ], $rows));

        // Keep the item's on-hand consistent with the movement history.
        $item->stock = $running;
        $item->save();
    }

    /**
     * @return array{0: StockMovementType, 1: int, 2: string}
     */
    private function randomChange(): array
    {
        return collect([
            [StockMovementType::Restock, random_int(15, 60), 'Supplier delivery'],
            [StockMovementType::Return, random_int(1, 6), 'Customer return'],
            [StockMovementType::Damage, -random_int(1, 10), 'Damaged in warehouse'],
            [StockMovementType::Correction, random_int(0, 1) ? random_int(1, 8) : -random_int(1, 8), 'Stock count correction'],
            [StockMovementType::Adjustment, random_int(0, 1) ? random_int(1, 12) : -random_int(1, 12), 'Manual adjustment'],
        ])->random();
    }
}
