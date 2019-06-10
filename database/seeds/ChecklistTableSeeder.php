<?php

use Illuminate\Database\Seeder;
use App\Models\Checklist;
use App\Models\Item;

class ChecklistTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Checklist::class, 10)
            ->create()
            ->each(function ($checklist) {
                foreach (range(1, 5) as $i) {
                    $checklist->items()->save(factory(Item::class)->make());
                }
            });
    }
}
