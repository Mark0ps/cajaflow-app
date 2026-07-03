<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    /**
     * Catálogo migrado de la hoja "LISTA DE PROVEEDORES" del Excel original.
     *
     * NOTA: excluí a propósito 4 filas que no son proveedores reales, sino
     * tipos de entrega interna que aparecían mezclados en el mismo dropdown:
     * "Entrega Vale a Personal", "Entrega a Claudeth", "Entrega a Marcos",
     * "Entrega a Carlos". Esos casos ahora los cubre el módulo de Vales,
     * no el catálogo de proveedores.
     *
     * También hay 2 entradas muy similares que decidí dejar ambas tal cual
     * estaban en el Excel por si refieren a razones sociales distintas:
     * "Boquitas Fiestas" y "BOQUITAS FIESTAS S. DE R.L." — revísalas y
     * fusiona si en realidad son el mismo proveedor.
     */
    private const PROVEEDORES = [
        'Abarroteria Fanny',
        'Abarroteria Katy',
        'Abarroteria y Comercial Valle',
        'Abarroteria y Confiteria San Judas #2',
        'Abarrotria Belen S. de R.L.',
        'Almacen El Canal',
        'Avicola Las Delicias S de RL',
        'Bimbo de Honduras S.A. de C.V.',
        'BNS Honduras S.A.',
        'Boquitas Fiestas',
        'Boquitas Fiestas S. de R.L.',
        'British American Tobacco Central America',
        'Cadeca S.A. de C.V.',
        'Carnes Lacteos y Mas Pedro',
        'Cerveceria Hondureña S.A.',
        'Comercial Lino',
        'Comercial y Productos Diversos Jose',
        'Comisariato Los Andres S.A. de C.V',
        'Confiteria Victoria',
        'Congelados de Honduras S.A DE C.V',
        'Diapa',
        'Dinai S. de R.L.',
        'Dinant',
        'Disanpe S.A. de C.V.',
        'Disna de Honduras S.A de C.V.',
        'Disol',
        'Distribuciones y Representaciones Gallegos',
        'Distribuidora Abigail',
        'Distribuidora Conga S. de R.L',
        'Distribuidora de Alimentos El Migo S.A.',
        'Distribuidora de Variedades',
        'Distribuidora Istmania, S.A. De C.V.',
        'Distribuidora Liberty',
        'Distribuidora Meambar',
        'Distribuidora Price Depot',
        'Distribuidora Rayovac Honduras',
        'Distribuidora Tovar',
        'Drogeria Proconsumo S.A. de C.V.',
        'Emsula Pepsi',
        'Genericos S. de R.L.',
        'Grupo Alfil S.A de C.V.',
        'Grupo Alza',
        'Grupo Casa Jade S de RL de CV',
        'H&C Trading S. de RL.',
        'H&M Inversiones S. de R.L. de C.V.',
        'Importadora Perdomo S. de R.L',
        'Industrias Sula S. de R.L.',
        'Inmecro',
        'Intercentro S.A.',
        'Inverfloram',
        'Inversiones C&F S. De R.L.',
        'Inversiones El Porvenir S.A. de CV.',
        'Inversiones Moviles S. De R.L',
        'Inversiones Ponce',
        'Inversiones Surtidora de Occidente',
        'Inversiones WYZ S. de R. L de C.V.',
        'Inversiones y Bodega M&M S D RL',
        'Inversiones y Bodega M&M S.R.L. de C.V.',
        'Jose Felix Licona Sanchez',
        'Karla',
        'Kuattro, S. de R.L',
        'La Colonia Supermercado',
        'La Provincia S de R L',
        'Lacteos Navarro',
        'Lacthosa',
        'Los Andes Panaderia',
        'Macdel de Honduras S.A.',
        'Materias Primas',
        'Mejores Productos Procesados S. de R.L.',
        'Mercado',
        'Molino Harinero Sula',
        'Oro Maya de Honduras S. de R.L.',
        'Panaderia Extra',
        'Panaderia y Reposteria Moderna',
        'Panistro',
        'PriceSmart de Honduras',
        'Relec S.A de C.V.',
        'Ropo Inversiones',
        'Rosquillera Los Pacos',
        'Salut Inversiones El Retiro S.A',
        'Satori Enterprise, S de R.L.',
        'Suministros & Servicios Agroindustriales',
        'Super Mercados Junior',
        'Supertienda Xtras S.A.',
        'Surtidora La Confianza',
        'Unami',
        'Utiles de Honduras S.A. de C.V.',
        'Aguacate',
        'Torta de Mantequilla',
        'Grupo Hond S.A.',
        'Inversiones Esquimal',
        'I. Confrutsa, S. de R.L.',
    ];

    public function run(): void
    {
        foreach (self::PROVEEDORES as $nombre) {
            Proveedor::firstOrCreate(
                ['nombre' => trim($nombre)],
                ['activo' => true]
            );
        }

        $this->command->info(count(self::PROVEEDORES) . ' proveedores sembrados.');
    }
}
