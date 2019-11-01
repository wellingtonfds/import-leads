<?php
use App\Brand;
use Illuminate\Database\Seeder;

class BrandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       $brands = [
           ['name'=>'ACQUAZERO'],
           ['name'=>'Encontre sua Viagem'],
           ['name'=>'Fórmula Pizzaria'],
           ['name'=>'QUISTO'],
           ['name'=>'SUAV'],
       ];
       foreach ($brands as $brand){
           Brand::create($brand);
       }
    }
}
