<?php

namespace App\Support;

/**
 * Catálogo de los 14 departamentos de El Salvador y sus municipios, usado en
 * el registro de pacientes (selects dependientes: elegir departamento filtra
 * los municipios disponibles). Es un dato geográfico fijo que no cambia, así
 * que vive como array embebido (mismo criterio que FontOptions/ColorThemes)
 * en vez de una tabla — no hace falta administrarlo desde el panel.
 *
 * Los municipios listados son los tradicionales (división histórica de 262
 * municipios), que es la que la mayoría de formularios salvadoreños sigue
 * usando en la práctica, independientemente de agrupaciones administrativas
 * posteriores en distritos.
 */
class ElSalvadorLocations
{
    public static function all(): array
    {
        return [
            'ahuachapan' => [
                'label' => 'Ahuachapán',
                'municipalities' => [
                    'Ahuachapán', 'Apaneca', 'Atiquizaya', 'Concepción de Ataco', 'El Refugio',
                    'Guaymango', 'Jujutla', 'San Francisco Menéndez', 'San Lorenzo',
                    'San Pedro Puxtla', 'Tacuba', 'Turín',
                ],
            ],
            'santa-ana' => [
                'label' => 'Santa Ana',
                'municipalities' => [
                    'Candelaria de la Frontera', 'Coatepeque', 'Chalchuapa', 'El Congo',
                    'El Porvenir', 'Masahuat', 'Metapán', 'San Antonio Pajonal', 'San Sebastián Salitrillo',
                    'Santa Ana', 'Santa Rosa Guachipilín', 'Santiago de la Frontera',
                    'Texistepeque',
                ],
            ],
            'sonsonate' => [
                'label' => 'Sonsonate',
                'municipalities' => [
                    'Acajutla', 'Armenia', 'Caluco', 'Cuisnahuat', 'Santa Isabel Ishuatán',
                    'Izalco', 'Juayúa', 'Nahuizalco', 'Nahulingo', 'Salcoatitán', 'San Antonio del Monte',
                    'San Julián', 'Santa Catarina Masahuat', 'Santo Domingo de Guzmán',
                    'Sonsonate', 'Sonzacate',
                ],
            ],
            'chalatenango' => [
                'label' => 'Chalatenango',
                'municipalities' => [
                    'Agua Caliente', 'Arcatao', 'Azacualpa', 'Chalatenango', 'Cital', 'Comalapa',
                    'Concepción Quezaltepeque', 'Dulce Nombre de María', 'El Carrizal',
                    'El Paraíso', 'La Laguna', 'La Palma', 'La Reina', 'Las Flores', 'las Vueltas',
                    'Nombre de Jesús', 'Nueva Concepción', 'Nueva Trinidad', 'Ojos de Agua',
                    'Potonico', 'San Antonio de la Cruz', 'San Antonio Los Ranchos',
                    'San Fernando', 'San Francisco Lempa', 'San Francisco Morazán',
                    'San Ignacio', 'San Isidro Labrador', 'San Luis del Carmen',
                    'San Miguel de Mercedes', 'San Rafael', 'Santa Rita', 'Tejutla',
                ],
            ],
            'la-libertad' => [
                'label' => 'La Libertad',
                'municipalities' => [
                    'Antiguo Cuscatlán', 'Chiltiupán', 'Ciudad Arce', 'Colón', 'Comasagua',
                    'Huizúcar', 'Jayaque', 'Jicalapa', 'La Libertad', 'Nuevo Cuscatlán',
                    'Quezaltepeque', 'Sacacoyo', 'San José Villanueva', 'San Juan Opico',
                    'San Matías', 'San Pablo Tacachico', 'Santa Tecla', 'Talnique', 'Tamanique',
                    'Teotepeque', 'Tepecoyo', 'Zaragoza',
                ],
            ],
            'san-salvador' => [
                'label' => 'San Salvador',
                'municipalities' => [
                    'Aguilares', 'Apopa', 'Ayutuxtepeque', 'Cuscatancingo', 'Delgado',
                    'El Paisnal', 'Guazapa', 'Ilopango', 'Mejicanos', 'Nejapa', 'Panchimalco',
                    'Rosario de Mora', 'San Marcos', 'San Martín', 'San Salvador',
                    'Santiago Texacuangos', 'Santo Tomás', 'Soyapango', 'Tonacatepeque',
                ],
            ],
            'cuscatlan' => [
                'label' => 'Cuscatlán',
                'municipalities' => [
                    'Candelaria', 'Cojutepeque', 'El Carmen', 'El Rosario', 'Monte San Juan',
                    'Oratorio de Concepción', 'San Bartolomé Perulapía', 'San Cristóbal',
                    'San José Guayabal', 'San Pedro Perulapán', 'San Rafael Cedros',
                    'San Ramón', 'Santa Cruz Analquito', 'Santa Cruz Michapa',
                    'Suchitoto', 'Tenancingo',
                ],
            ],
            'la-paz' => [
                'label' => 'La Paz',
                'municipalities' => [
                    'Cuyultitán', 'El Rosario', 'Jerusalén', 'Mercedes La Ceiba', 'Olocuilta',
                    'Paraíso de Osorio', 'San Antonio Masahuat', 'San Emigdio', 'San Francisco Chinameca',
                    'San Juan Nonualco', 'San Juan Talpa', 'San Juan Tepezontes',
                    'San Luis La Herradura', 'San Luis Talpa', 'San Miguel Tepezontes',
                    'San Pedro Masahuat', 'San Pedro Nonualco', 'San Rafael Obrajuelo',
                    'Santa María Ostuma', 'Santiago Nonualco', 'Tapalhuaca', 'Zacatecoluca',
                ],
            ],
            'cabanas' => [
                'label' => 'Cabañas',
                'municipalities' => [
                    'Cinquera', 'Dolores', 'Guacotecti', 'Ilobasco', 'Jutiapa',
                    'San Isidro', 'Sensuntepeque', 'Tejutepeque', 'Victoria',
                ],
            ],
            'san-vicente' => [
                'label' => 'San Vicente',
                'municipalities' => [
                    'Apastepeque', 'Guadalupe', 'San Cayetano Istepeque', 'San Esteban Catarina',
                    'San Ildefonso', 'San Lorenzo', 'San Sebastián', 'San Vicente',
                    'Santa Clara', 'Santo Domingo', 'Tecoluca', 'Tepetitán', 'Verapaz',
                ],
            ],
            'usulutan' => [
                'label' => 'Usulután',
                'municipalities' => [
                    'Alegría', 'Berlín', 'California', 'Concepción Batres', 'El Triunfo',
                    'Ereguayquín', 'Estanzuelas', 'Jiquilisco', 'Jucuapa', 'Jucuarán',
                    'Mercedes Umaña', 'Nueva Granada', 'Ozatlán', 'Puerto El Triunfo',
                    'San Agustín', 'San Buenaventura', 'San Dionisio', 'San Francisco Javier',
                    'Santa Elena', 'Santa María', 'Santiago de María', 'Tecapán', 'Usulután',
                ],
            ],
            'san-miguel' => [
                'label' => 'San Miguel',
                'municipalities' => [
                    'Carolina', 'Chapeltique', 'Chinameca', 'Chirilagua', 'Ciudad Barrios',
                    'Comacarán', 'El Tránsito', 'Lolotique', 'Moncagua', 'Nueva Guadalupe',
                    'Nuevo Edén de San Juan', 'Quelepa', 'San Antonio del Mosco', 'San Gerardo',
                    'San Jorge', 'San Luis de la Reina', 'San Miguel', 'San Rafael Oriente',
                    'Sesori', 'Uluazapa',
                ],
            ],
            'morazan' => [
                'label' => 'Morazán',
                'municipalities' => [
                    'Arambala', 'Cacaopera', 'Chilanga', 'Corinto', 'Delicias de Concepción',
                    'El Divisadero', 'El Rosario', 'Gualococti', 'Guatajiagua', 'Joateca',
                    'Jocoaitique', 'Jocoro', 'Lolotiquillo', 'Meanguera', 'Osicala',
                    'Perquín', 'San Carlos', 'San Fernando', 'San Francisco Gotera',
                    'San Isidro', 'San Simón', 'Sensembra', 'Sociedad', 'Torola', 'Yamabal',
                    'Yoloaiquín',
                ],
            ],
            'la-union' => [
                'label' => 'La Unión',
                'municipalities' => [
                    'Anamorós', 'Bolívar', 'Concepción de Oriente', 'Conchagua', 'El Carmen',
                    'El Sauce', 'Intipucá', 'La Unión', 'Lislique', 'Meanguera del Golfo',
                    'Nueva Esparta', 'Pasaquina', 'Polorós', 'San Alejo', 'San José',
                    'Santa Rosa de Lima', 'Yayantique', 'Yucuaiquín',
                ],
            ],
        ];
    }

    public static function departments(): array
    {
        return collect(self::all())->map(fn ($d) => $d['label'])->all();
    }

    public static function municipalitiesFor(?string $departmentKey): array
    {
        return self::all()[$departmentKey]['municipalities'] ?? [];
    }
}
