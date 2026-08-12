<?php

/**
 * Sample lineup catalog: brand_slug => list of vehicles.
 * Optional keys: commons, specs, model_year, slug.
 */
return [
    'toyota' => [
        ['name' => 'Avanza', 'body_type' => 'MPV', 'price_from' => 236900000, 'excerpt' => 'MPV keluarga andalan Toyota dengan kabin 7 penumpang.', 'commons' => 'File:Toyota Avanza 1.3 E 2024 (2).jpg', 'specs' => [
            ['label' => 'Mesin', 'value' => '1.5L Dual VVT-i'], ['label' => 'Tenaga maks.', 'value' => '106 PS'], ['label' => 'Torsi maks.', 'value' => '138 Nm'], ['label' => 'Transmisi', 'value' => '5MT / 4AT / CVT'], ['label' => 'Penggerak', 'value' => 'FWD'], ['label' => 'Kapasitas', 'value' => '7 penumpang'], ['label' => 'BBM', 'value' => 'Bensin'], ['label' => 'Dimensi (P×L×T)', 'value' => '4.395 × 1.730 × 1.700 mm'],
        ]],
        ['name' => 'Fortuner', 'body_type' => 'SUV', 'price_from' => 661200000, 'excerpt' => 'SUV body-on-frame untuk keluarga dan medan berat.', 'commons' => 'File:2021 Toyota Fortuner SRZ 2.7 4x2 (Indonesia) front view 01.jpg'],
        ['name' => 'Innova Zenix', 'body_type' => 'MPV', 'price_from' => 414800000, 'excerpt' => 'MPV premium berbasis TNGA dengan opsi hybrid.', 'commons' => 'File:2023 Kijang Innova Zenix G HV.jpg'],
        ['name' => 'Corolla Cross', 'body_type' => 'Crossover', 'price_from' => 514200000, 'excerpt' => 'Crossover hybrid hemat BBM berbasis Corolla.', 'commons' => 'File:Toyota Corolla Cross Hybrid 1X7A1861.jpg'],
    ],
    'honda' => [
        ['name' => 'CR-V', 'body_type' => 'SUV', 'price_from' => 759900000, 'excerpt' => 'SUV Honda premium dengan opsi hybrid e:HEV.', 'commons' => 'File:2023 Honda CR-V EX-L 4WD in Radiant Red Metallic, rear right.jpg'],
        ['name' => 'City Hatchback', 'body_type' => 'Hatchback', 'price_from' => 323900000, 'excerpt' => 'Hatchback stylish untuk mobilitas kota.'],
        ['name' => 'BeAT', 'slug' => 'beat', 'body_type' => 'Scooter', 'price_from' => 18710000, 'excerpt' => 'Skutik entry Honda paling laris di Indonesia.', 'commons' => 'File:2020 Honda BeAT CBS 110 (20200622).jpg'],
        ['name' => 'PCX', 'body_type' => 'Scooter', 'price_from' => 32955000, 'excerpt' => 'Maxi scooter premium Honda dengan fitur lengkap.'],
    ],
    'mitsubishi' => [
        ['name' => 'Xpander', 'body_type' => 'MPV', 'price_from' => 254100000, 'excerpt' => 'MPV crossover favorit keluarga Indonesia.'],
        ['name' => 'Pajero Sport', 'body_type' => 'SUV', 'price_from' => 572100000, 'excerpt' => 'SUV tangguh dengan kemampuan off-road.'],
        ['name' => 'Xforce', 'body_type' => 'SUV', 'price_from' => 351500000, 'excerpt' => 'SUV compact modern dengan desain tegas.'],
    ],
    'suzuki' => [
        ['name' => 'Ertiga', 'body_type' => 'MPV', 'price_from' => 242800000, 'excerpt' => 'MPV praktis dan irit untuk keluarga.'],
        ['name' => 'XL7', 'body_type' => 'SUV', 'price_from' => 262300000, 'excerpt' => 'SUV 7-seater dengan gaya crossover.'],
        ['name' => 'Nex II', 'body_type' => 'Scooter', 'price_from' => 16700000, 'excerpt' => 'Skutik ringan dan efisien untuk harian.'],
        ['name' => 'GSX-R150', 'body_type' => 'Sport', 'price_from' => 36900000, 'excerpt' => 'Sportbike entry dengan DNA racing Suzuki.'],
    ],
    'daihatsu' => [
        ['name' => 'Sigra', 'body_type' => 'MPV', 'price_from' => 146800000, 'excerpt' => 'LPV hemat untuk keluarga muda.'],
        ['name' => 'Terios', 'body_type' => 'SUV', 'price_from' => 246600000, 'excerpt' => 'SUV 7-seater andalan Daihatsu.'],
        ['name' => 'Rocky', 'body_type' => 'SUV', 'price_from' => 228600000, 'excerpt' => 'Compact SUV stylish berbasis DNGA.'],
    ],
    'nissan' => [
        ['name' => 'Livina', 'body_type' => 'MPV', 'price_from' => 230000000, 'excerpt' => 'MPV keluarga dengan kabin lapang.'],
        ['name' => 'Magnite', 'body_type' => 'SUV', 'price_from' => 220000000, 'excerpt' => 'Compact SUV stylish untuk generasi muda.'],
        ['name' => 'Serena', 'body_type' => 'MPV', 'price_from' => 520000000, 'excerpt' => 'MPV premium dengan opsi e-POWER.'],
    ],
    'mazda' => [
        ['name' => 'CX-5', 'body_type' => 'SUV', 'price_from' => 589800000, 'excerpt' => 'SUV premium dengan desain Kodo dan handling tajam.'],
        ['name' => 'CX-3', 'body_type' => 'SUV', 'price_from' => 379900000, 'excerpt' => 'Compact SUV urban dengan gaya premium.'],
        ['name' => '2 Hatchback', 'slug' => 'mazda-2', 'body_type' => 'Hatchback', 'price_from' => 289800000, 'excerpt' => 'City hatchback lincah dan stylish.'],
    ],
    'hyundai' => [
        ['name' => 'Creta', 'body_type' => 'SUV', 'price_from' => 299000000, 'excerpt' => 'Compact SUV populer dengan fitur ADAS.'],
        ['name' => 'Stargazer', 'body_type' => 'MPV', 'price_from' => 245000000, 'excerpt' => 'MPV modern dengan desain futuristik.'],
        ['name' => 'Ioniq 5', 'body_type' => 'EV', 'price_from' => 825000000, 'excerpt' => 'Crossover listrik ikonik berbasis E-GMP.'],
    ],
    'kia' => [
        ['name' => 'Carens', 'body_type' => 'MPV', 'price_from' => 349000000, 'excerpt' => 'MPV stylish dengan kabin fleksibel.'],
        ['name' => 'Seltos', 'body_type' => 'SUV', 'price_from' => 349000000, 'excerpt' => 'Compact SUV penuh fitur hiburan.'],
        ['name' => 'EV5', 'body_type' => 'EV', 'price_from' => 750000000, 'excerpt' => 'SUV listrik keluarga dengan jarak tempuh jauh.'],
    ],
    'isuzu' => [
        ['name' => 'mu-X', 'slug' => 'mu-x', 'body_type' => 'SUV', 'price_from' => 559000000, 'excerpt' => 'SUV diesel tangguh berbasis pickup.'],
        ['name' => 'D-Max', 'body_type' => 'Pickup', 'price_from' => 335000000, 'excerpt' => 'Pickup andalan untuk kerja dan petualangan.'],
    ],
    'subaru' => [
        ['name' => 'Forester', 'body_type' => 'SUV', 'price_from' => 650000000, 'excerpt' => 'SUV AWD dengan karakter petualangan.'],
        ['name' => 'XV', 'body_type' => 'Crossover', 'price_from' => 480000000, 'excerpt' => 'Crossover AWD untuk kota dan ringan off-road.'],
    ],
    'bmw' => [
        ['name' => 'X5', 'slug' => 'x5', 'body_type' => 'SUV', 'price_from' => 1899000000, 'excerpt' => 'Luxury SAV BMW dengan performa xDrive.', 'commons' => 'File:BMW X5 (G05) China.jpg'],
        ['name' => '320i', 'body_type' => 'Sedan', 'price_from' => 999000000, 'excerpt' => 'Sedan sporty Seri 3 untuk driving pleasure.'],
        ['name' => 'G310 R', 'body_type' => 'Naked', 'price_from' => 99000000, 'excerpt' => 'Naked bike entry BMW Motorrad.'],
    ],
    'mercedes-benz' => [
        ['name' => 'C-Class', 'slug' => 'c-class', 'body_type' => 'Sedan', 'price_from' => 1200000000, 'excerpt' => 'Sedan eksekutif dengan kemewahan khas Mercedes.'],
        ['name' => 'GLC', 'body_type' => 'SUV', 'price_from' => 1450000000, 'excerpt' => 'SUV premium serbaguna untuk keluarga eksekutif.'],
        ['name' => 'A-Class', 'slug' => 'a-class', 'body_type' => 'Hatchback', 'price_from' => 800000000, 'excerpt' => 'Compact luxury hatchback modern.'],
    ],
    'audi' => [
        ['name' => 'Q5', 'body_type' => 'SUV', 'price_from' => 1450000000, 'excerpt' => 'SUV premium quattro dengan kabin high-tech.'],
        ['name' => 'A4', 'body_type' => 'Sedan', 'price_from' => 1100000000, 'excerpt' => 'Sedan bisnis sporty dengan teknologi MMI.'],
    ],
    'lexus' => [
        ['name' => 'RX', 'body_type' => 'SUV', 'price_from' => 1800000000, 'excerpt' => 'Luxury crossover hybrid Lexus.'],
        ['name' => 'NX', 'body_type' => 'SUV', 'price_from' => 1300000000, 'excerpt' => 'Compact luxury SUV dengan desain tegas.'],
    ],
    'porsche' => [
        ['name' => '911 Carrera', 'slug' => '911-carrera', 'body_type' => 'Sport', 'price_from' => 4500000000, 'excerpt' => 'Ikon sportscar rear-engine Porsche.'],
        ['name' => 'Cayenne', 'body_type' => 'SUV', 'price_from' => 2800000000, 'excerpt' => 'SUV performa tinggi khas Porsche.'],
        ['name' => 'Macan', 'body_type' => 'SUV', 'price_from' => 1800000000, 'excerpt' => 'Compact SUV sporty untuk harian dan weekend.'],
    ],
    'land-rover' => [
        ['name' => 'Range Rover Sport', 'slug' => 'range-rover-sport', 'body_type' => 'SUV', 'price_from' => 3200000000, 'excerpt' => 'SUV mewah dengan kapabilitas off-road legendaris.'],
        ['name' => 'Defender', 'body_type' => 'SUV', 'price_from' => 2500000000, 'excerpt' => 'SUV petualang ikonik generasi terbaru.'],
    ],
    'jaguar' => [
        ['name' => 'F-Pace', 'body_type' => 'SUV', 'price_from' => 1800000000, 'excerpt' => 'Performance SUV dengan karakter Jaguar.'],
        ['name' => 'XE', 'body_type' => 'Sedan', 'price_from' => 1200000000, 'excerpt' => 'Sedan sporty kompak Jaguar.'],
    ],
    'volvo' => [
        ['name' => 'XC60', 'body_type' => 'SUV', 'price_from' => 1400000000, 'excerpt' => 'SUV premium berfokus pada keselamatan.'],
        ['name' => 'XC40', 'body_type' => 'SUV', 'price_from' => 900000000, 'excerpt' => 'Compact SUV modern dengan opsi listrik.'],
    ],
    'mini' => [
        ['name' => 'Cooper', 'body_type' => 'Hatchback', 'price_from' => 780000000, 'excerpt' => 'Hatchback ikonik dengan handling lincah.'],
        ['name' => 'Countryman', 'body_type' => 'Crossover', 'price_from' => 950000000, 'excerpt' => 'Crossover MINI untuk gaya hidup aktif.'],
    ],
    'bentley' => [
        ['name' => 'Continental GT', 'slug' => 'continental-gt', 'body_type' => 'Coupe', 'price_from' => 9000000000, 'excerpt' => 'Grand tourer mewah buatan tangan.'],
        ['name' => 'Bentayga', 'body_type' => 'SUV', 'price_from' => 10000000000, 'excerpt' => 'SUV ultra-luxury Bentley.'],
    ],
    'rolls-royce' => [
        ['name' => 'Ghost', 'body_type' => 'Sedan', 'price_from' => 12000000000, 'excerpt' => 'Sedan ultra-luxury dengan kenyamanan tertinggi.'],
        ['name' => 'Cullinan', 'body_type' => 'SUV', 'price_from' => 14000000000, 'excerpt' => 'SUV mewah paling eksklusif Rolls-Royce.'],
    ],
    'ferrari' => [
        ['name' => 'Roma', 'body_type' => 'Coupe', 'price_from' => 8500000000, 'excerpt' => 'Gran turismo elegan dari Maranello.'],
        ['name' => 'SF90 Stradale', 'slug' => 'sf90-stradale', 'body_type' => 'Sport', 'price_from' => 15000000000, 'excerpt' => 'Supercar hybrid plug-in performa ekstrem.'],
    ],
    'lamborghini' => [
        ['name' => 'Urus', 'body_type' => 'SUV', 'price_from' => 9000000000, 'excerpt' => 'Super SUV dengan DNA Lamborghini.'],
        ['name' => 'Huracán', 'body_type' => 'Sport', 'price_from' => 8000000000, 'excerpt' => 'Supercar V10 ikonik Sant’Agata.'],
    ],
    'maserati' => [
        ['name' => 'Grecale', 'body_type' => 'SUV', 'price_from' => 2200000000, 'excerpt' => 'Compact luxury SUV berkarakter Italia.'],
        ['name' => 'Ghibli', 'body_type' => 'Sedan', 'price_from' => 2500000000, 'excerpt' => 'Sedan sporty mewah Maserati.'],
    ],
    'mclaren' => [
        ['name' => 'Artura', 'body_type' => 'Sport', 'price_from' => 7000000000, 'excerpt' => 'Supercar hybrid ringan generasi baru.'],
        ['name' => '750S', 'body_type' => 'Sport', 'price_from' => 9000000000, 'excerpt' => 'Supercar twin-turbo V8 ekstrem.'],
    ],
    'aston-martin' => [
        ['name' => 'DB12', 'body_type' => 'Coupe', 'price_from' => 8000000000, 'excerpt' => 'Grand tourer British luxury.'],
        ['name' => 'DBX', 'body_type' => 'SUV', 'price_from' => 7500000000, 'excerpt' => 'SUV performa tinggi Aston Martin.'],
    ],
    'genesis' => [
        ['name' => 'G80', 'body_type' => 'Sedan', 'price_from' => 1600000000, 'excerpt' => 'Sedan luxury Korea dengan kabin senyap.'],
        ['name' => 'GV70', 'body_type' => 'SUV', 'price_from' => 1500000000, 'excerpt' => 'Luxury SUV stylish Genesis.'],
    ],
    'infiniti' => [
        ['name' => 'QX60', 'body_type' => 'SUV', 'price_from' => 1400000000, 'excerpt' => 'SUV 3-baris premium Infiniti.'],
        ['name' => 'Q50', 'body_type' => 'Sedan', 'price_from' => 1000000000, 'excerpt' => 'Sedan sporty Infiniti.'],
    ],
    'cadillac' => [
        ['name' => 'Escalade', 'body_type' => 'SUV', 'price_from' => 3500000000, 'excerpt' => 'Full-size luxury SUV Amerika.'],
        ['name' => 'CT5', 'body_type' => 'Sedan', 'price_from' => 1500000000, 'excerpt' => 'Sedan eksekutif Cadillac.'],
    ],
    'alfa-romeo' => [
        ['name' => 'Giulia', 'body_type' => 'Sedan', 'price_from' => 1400000000, 'excerpt' => 'Sedan sporty Italia dengan karakter tajam.'],
        ['name' => 'Stelvio', 'body_type' => 'SUV', 'price_from' => 1600000000, 'excerpt' => 'SUV performa Alfa Romeo.'],
    ],
    'volkswagen' => [
        ['name' => 'Tiguan', 'body_type' => 'SUV', 'price_from' => 750000000, 'excerpt' => 'SUV keluarga Jerman yang praktis.'],
        ['name' => 'Golf GTI', 'slug' => 'golf-gti', 'body_type' => 'Hatchback', 'price_from' => 850000000, 'excerpt' => 'Hot hatch ikonik Volkswagen.'],
        ['name' => 'ID.4', 'slug' => 'id4', 'body_type' => 'EV', 'price_from' => 900000000, 'excerpt' => 'SUV listrik berbasis platform MEB.'],
    ],
    'peugeot' => [
        ['name' => '3008', 'body_type' => 'SUV', 'price_from' => 650000000, 'excerpt' => 'SUV Prancis dengan i-Cockpit khas.'],
        ['name' => '5008', 'body_type' => 'SUV', 'price_from' => 750000000, 'excerpt' => 'SUV 7-seater stylish Peugeot.'],
    ],
    'renault' => [
        ['name' => 'Koleos', 'body_type' => 'SUV', 'price_from' => 550000000, 'excerpt' => 'SUV keluarga nyaman dari Renault.'],
        ['name' => 'Triber', 'body_type' => 'MPV', 'price_from' => 180000000, 'excerpt' => 'MPV modular 7-seater terjangkau.'],
    ],
    'citroen' => [
        ['name' => 'C5 Aircross', 'slug' => 'c5-aircross', 'body_type' => 'SUV', 'price_from' => 550000000, 'excerpt' => 'SUV nyaman dengan Progressive Hydraulic Cushions.'],
        ['name' => 'C3', 'body_type' => 'Hatchback', 'price_from' => 280000000, 'excerpt' => 'City hatchback bergaya Prancis.'],
    ],
    'opel' => [
        ['name' => 'Mokka', 'body_type' => 'SUV', 'price_from' => 450000000, 'excerpt' => 'Compact SUV modern Opel.'],
        ['name' => 'Grandland', 'body_type' => 'SUV', 'price_from' => 550000000, 'excerpt' => 'SUV keluarga Opel.'],
    ],
    'fiat' => [
        ['name' => '500', 'slug' => 'fiat-500', 'body_type' => 'Hatchback', 'price_from' => 450000000, 'excerpt' => 'City car ikonik Italia.'],
        ['name' => 'Pulse', 'body_type' => 'SUV', 'price_from' => 350000000, 'excerpt' => 'Compact SUV Fiat untuk gaya hidup urban.'],
    ],
    'ford' => [
        ['name' => 'Ranger', 'body_type' => 'Pickup', 'price_from' => 420000000, 'excerpt' => 'Pickup tangguh untuk kerja dan adventure.'],
        ['name' => 'Everest', 'body_type' => 'SUV', 'price_from' => 720000000, 'excerpt' => 'SUV berbasis Ranger untuk keluarga.'],
        ['name' => 'Territory', 'body_type' => 'SUV', 'price_from' => 480000000, 'excerpt' => 'SUV modern dengan fitur teknologi lengkap.'],
    ],
    'chevrolet' => [
        ['name' => 'Colorado', 'body_type' => 'Pickup', 'price_from' => 400000000, 'excerpt' => 'Pickup Amerika untuk berbagai medan.'],
        ['name' => 'Traverse', 'body_type' => 'SUV', 'price_from' => 900000000, 'excerpt' => 'SUV 3-baris lapang Chevrolet.'],
    ],
    'jeep' => [
        ['name' => 'Wrangler', 'body_type' => 'SUV', 'price_from' => 1600000000, 'excerpt' => 'SUV off-road ikonik Jeep.'],
        ['name' => 'Compass', 'body_type' => 'SUV', 'price_from' => 750000000, 'excerpt' => 'Compact SUV Jeep untuk kota dan ringan off-road.'],
    ],
    'dodge' => [
        ['name' => 'Hornet', 'body_type' => 'SUV', 'price_from' => 800000000, 'excerpt' => 'Compact SUV sporty Dodge.'],
        ['name' => 'Charger', 'body_type' => 'Sedan', 'price_from' => 1500000000, 'excerpt' => 'Muscle sedan Amerika yang ikonik.'],
    ],
    'gmc' => [
        ['name' => 'Yukon', 'body_type' => 'SUV', 'price_from' => 2500000000, 'excerpt' => 'Full-size SUV premium GMC.'],
        ['name' => 'Sierra', 'body_type' => 'Pickup', 'price_from' => 1800000000, 'excerpt' => 'Pickup full-size tangguh GMC.'],
    ],
    'ram' => [
        ['name' => '1500', 'slug' => 'ram-1500', 'body_type' => 'Pickup', 'price_from' => 1600000000, 'excerpt' => 'Pickup full-size nyaman dan bertenaga.'],
        ['name' => '2500', 'slug' => 'ram-2500', 'body_type' => 'Pickup', 'price_from' => 2000000000, 'excerpt' => 'Heavy-duty pickup untuk beban berat.'],
    ],
    'skoda' => [
        ['name' => 'Kodiaq', 'body_type' => 'SUV', 'price_from' => 700000000, 'excerpt' => 'SUV 7-seater praktis Skoda.'],
        ['name' => 'Kamiq', 'body_type' => 'SUV', 'price_from' => 450000000, 'excerpt' => 'Compact SUV urban Skoda.'],
    ],
    'seat' => [
        ['name' => 'Ateca', 'body_type' => 'SUV', 'price_from' => 550000000, 'excerpt' => 'SUV sporty SEAT.'],
        ['name' => 'Leon', 'body_type' => 'Hatchback', 'price_from' => 500000000, 'excerpt' => 'Hatchback dinamis SEAT.'],
    ],
    'cupra' => [
        ['name' => 'Formentor', 'body_type' => 'SUV', 'price_from' => 700000000, 'excerpt' => 'Performance crossover Cupra.'],
        ['name' => 'Born', 'body_type' => 'EV', 'price_from' => 750000000, 'excerpt' => 'Hatchback listrik performa Cupra.'],
    ],
    'byd' => [
        ['name' => 'Atto 3', 'slug' => 'atto-3', 'body_type' => 'EV', 'price_from' => 475000000, 'excerpt' => 'SUV listrik populer BYD di Indonesia.'],
        ['name' => 'Seal', 'body_type' => 'EV', 'price_from' => 629000000, 'excerpt' => 'Sedan listrik performa BYD.'],
        ['name' => 'M6', 'body_type' => 'MPV', 'price_from' => 383000000, 'excerpt' => 'MPV listrik keluarga BYD.'],
    ],
    'wuling' => [
        ['name' => 'Air EV', 'slug' => 'air-ev', 'body_type' => 'EV', 'price_from' => 178000000, 'excerpt' => 'City EV mungil dan irit operasional.'],
        ['name' => 'Almaz RS', 'slug' => 'almaz-rs', 'body_type' => 'SUV', 'price_from' => 340000000, 'excerpt' => 'SUV Wuling dengan fitur cloud.'],
        ['name' => 'Binguo EV', 'slug' => 'binguo-ev', 'body_type' => 'EV', 'price_from' => 249000000, 'excerpt' => 'Hatchback listrik stylish Wuling.'],
    ],
    'chery' => [
        ['name' => 'Omoda 5', 'slug' => 'omoda-5-chery', 'body_type' => 'SUV', 'price_from' => 309000000, 'excerpt' => 'Compact SUV stylish Chery.'],
        ['name' => 'Tiggo 8 Pro', 'slug' => 'tiggo-8-pro', 'body_type' => 'SUV', 'price_from' => 389000000, 'excerpt' => 'SUV 7-seater berfitur lengkap.'],
    ],
    'gwm' => [
        ['name' => 'Haval Jolion', 'slug' => 'haval-jolion', 'body_type' => 'SUV', 'price_from' => 349000000, 'excerpt' => 'Compact SUV GWM untuk keluarga muda.'],
        ['name' => 'Tank 300', 'slug' => 'tank-300-gwm', 'body_type' => 'SUV', 'price_from' => 720000000, 'excerpt' => 'Off-road SUV bergaya adventure.'],
    ],
    'mg' => [
        ['name' => 'ZS EV', 'slug' => 'zs-ev', 'body_type' => 'EV', 'price_from' => 418000000, 'excerpt' => 'SUV listrik terjangkau MG.'],
        ['name' => 'HS', 'body_type' => 'SUV', 'price_from' => 379000000, 'excerpt' => 'SUV midsize stylish MG.'],
        ['name' => '4 EV', 'slug' => 'mg4-ev', 'body_type' => 'EV', 'price_from' => 419000000, 'excerpt' => 'Hatchback listrik handling lincah.'],
    ],
    'tesla' => [
        ['name' => 'Model 3', 'slug' => 'model-3', 'body_type' => 'EV', 'price_from' => 900000000, 'excerpt' => 'Sedan listrik ikonik Tesla.'],
        ['name' => 'Model Y', 'slug' => 'model-y', 'body_type' => 'EV', 'price_from' => 1100000000, 'excerpt' => 'SUV listrik best-seller Tesla.'],
    ],
    'neta' => [
        ['name' => 'V-II', 'slug' => 'neta-v-ii', 'body_type' => 'EV', 'price_from' => 295000000, 'excerpt' => 'City EV Neta untuk mobilitas harian.'],
        ['name' => 'X', 'slug' => 'neta-x', 'body_type' => 'EV', 'price_from' => 450000000, 'excerpt' => 'SUV listrik keluarga Neta.'],
    ],
    'xpeng' => [
        ['name' => 'G6', 'slug' => 'xpeng-g6', 'body_type' => 'EV', 'price_from' => 750000000, 'excerpt' => 'SUV listrik dengan ADAS canggih.'],
        ['name' => 'P7', 'slug' => 'xpeng-p7', 'body_type' => 'EV', 'price_from' => 850000000, 'excerpt' => 'Sedan listrik performa XPeng.'],
    ],
    'nio' => [
        ['name' => 'ES6', 'body_type' => 'EV', 'price_from' => 1200000000, 'excerpt' => 'SUV listrik premium dengan battery swap.'],
        ['name' => 'ET5', 'body_type' => 'EV', 'price_from' => 1100000000, 'excerpt' => 'Sedan listrik NIO berteknologi tinggi.'],
    ],
    'zeekr' => [
        ['name' => '001', 'slug' => 'zeekr-001', 'body_type' => 'EV', 'price_from' => 1200000000, 'excerpt' => 'Shooting brake listrik premium Zeekr.'],
        ['name' => 'X', 'slug' => 'zeekr-x', 'body_type' => 'EV', 'price_from' => 900000000, 'excerpt' => 'Compact SUV listrik stylish Zeekr.'],
    ],
    'geely' => [
        ['name' => 'Coolray', 'body_type' => 'SUV', 'price_from' => 280000000, 'excerpt' => 'Compact SUV sporty Geely.'],
        ['name' => 'Okavango', 'body_type' => 'SUV', 'price_from' => 380000000, 'excerpt' => 'SUV 7-seater keluarga Geely.'],
    ],
    'haval' => [
        ['name' => 'H6', 'slug' => 'haval-h6', 'body_type' => 'SUV', 'price_from' => 380000000, 'excerpt' => 'SUV global Haval dengan fitur lengkap.'],
        ['name' => 'Jolion', 'slug' => 'jolion', 'body_type' => 'SUV', 'price_from' => 349000000, 'excerpt' => 'Compact SUV Haval untuk urban lifestyle.'],
    ],
    'tank' => [
        ['name' => '300', 'slug' => 'tank-300', 'body_type' => 'SUV', 'price_from' => 720000000, 'excerpt' => 'Off-road SUV bergaya klasik modern.'],
        ['name' => '500', 'slug' => 'tank-500', 'body_type' => 'SUV', 'price_from' => 1200000000, 'excerpt' => 'Full-size luxury off-road SUV.'],
    ],
    'ora' => [
        ['name' => '03', 'slug' => 'ora-03', 'body_type' => 'EV', 'price_from' => 350000000, 'excerpt' => 'City EV stylish Ora.'],
        ['name' => '07', 'slug' => 'ora-07', 'body_type' => 'EV', 'price_from' => 550000000, 'excerpt' => 'Sedan listrik Ora dengan desain retro-futuristik.'],
    ],
    'dfsk' => [
        ['name' => 'Glory 560', 'slug' => 'glory-560', 'body_type' => 'SUV', 'price_from' => 230000000, 'excerpt' => 'SUV 7-seater terjangkau DFSK.'],
        ['name' => 'Gelora E', 'slug' => 'gelora-e', 'body_type' => 'EV', 'price_from' => 350000000, 'excerpt' => 'Blind van listrik untuk bisnis.'],
    ],
    'seres' => [
        ['name' => 'E1', 'slug' => 'seres-e1', 'body_type' => 'EV', 'price_from' => 250000000, 'excerpt' => 'Mini EV Seres untuk kota.'],
        ['name' => '3', 'slug' => 'seres-3', 'body_type' => 'EV', 'price_from' => 450000000, 'excerpt' => 'SUV listrik Seres.'],
    ],
    'maxus' => [
        ['name' => 'MIFA 9', 'slug' => 'mifa-9', 'body_type' => 'MPV', 'price_from' => 1100000000, 'excerpt' => 'MPV listrik premium Maxus.'],
        ['name' => 'T60', 'body_type' => 'Pickup', 'price_from' => 350000000, 'excerpt' => 'Pickup modern Maxus.'],
    ],
    'jaecoo' => [
        ['name' => 'J7', 'slug' => 'jaecoo-j7', 'body_type' => 'SUV', 'price_from' => 379000000, 'excerpt' => 'SUV adventure-oriented Jaecoo.'],
        ['name' => 'J5', 'slug' => 'jaecoo-j5', 'body_type' => 'SUV', 'price_from' => 320000000, 'excerpt' => 'Compact SUV stylish Jaecoo.'],
    ],
    'omoda' => [
        ['name' => '5', 'slug' => 'omoda-5', 'body_type' => 'SUV', 'price_from' => 309000000, 'excerpt' => 'Compact SUV desain futuristik Omoda.'],
        ['name' => 'E5', 'slug' => 'omoda-e5', 'body_type' => 'EV', 'price_from' => 450000000, 'excerpt' => 'Versi listrik dari Omoda 5.'],
    ],
    'vinfast' => [
        ['name' => 'VF 5', 'slug' => 'vf-5', 'body_type' => 'EV', 'price_from' => 290000000, 'excerpt' => 'City SUV listrik VinFast.'],
        ['name' => 'VF 6', 'slug' => 'vf-6', 'body_type' => 'EV', 'price_from' => 450000000, 'excerpt' => 'Compact SUV listrik VinFast.'],
        ['name' => 'VF 7', 'slug' => 'vf-7', 'body_type' => 'EV', 'price_from' => 580000000, 'excerpt' => 'SUV listrik midsize VinFast.'],
    ],
    'aion' => [
        ['name' => 'Y Plus', 'slug' => 'aion-y-plus', 'body_type' => 'EV', 'price_from' => 420000000, 'excerpt' => 'SUV listrik keluarga Aion.'],
        ['name' => 'V', 'slug' => 'aion-v', 'body_type' => 'EV', 'price_from' => 480000000, 'excerpt' => 'SUV listrik stylish Aion.'],
    ],
    'deepal' => [
        ['name' => 'S07', 'slug' => 'deepal-s07', 'body_type' => 'EV', 'price_from' => 550000000, 'excerpt' => 'SUV listrik teknologi tinggi Deepal.'],
        ['name' => 'L07', 'slug' => 'deepal-l07', 'body_type' => 'EV', 'price_from' => 520000000, 'excerpt' => 'Sedan listrik aerodinamis Deepal.'],
    ],
    'smart' => [
        ['name' => '#1', 'slug' => 'smart-1', 'body_type' => 'EV', 'price_from' => 700000000, 'excerpt' => 'Compact SUV listrik kolaborasi smart.'],
        ['name' => '#3', 'slug' => 'smart-3', 'body_type' => 'EV', 'price_from' => 750000000, 'excerpt' => 'Crossover listrik sporty smart.'],
    ],
    'polestar' => [
        ['name' => '2', 'slug' => 'polestar-2', 'body_type' => 'EV', 'price_from' => 1100000000, 'excerpt' => 'Fastback listrik performa Polestar.'],
        ['name' => '4', 'slug' => 'polestar-4', 'body_type' => 'EV', 'price_from' => 1400000000, 'excerpt' => 'SUV-coupe listrik premium Polestar.'],
    ],
    'rivian' => [
        ['name' => 'R1T', 'body_type' => 'Pickup', 'price_from' => 2500000000, 'excerpt' => 'Electric adventure pickup Rivian.'],
        ['name' => 'R1S', 'body_type' => 'SUV', 'price_from' => 2600000000, 'excerpt' => 'Electric adventure SUV Rivian.'],
    ],
    'lucid' => [
        ['name' => 'Air', 'slug' => 'lucid-air', 'body_type' => 'EV', 'price_from' => 2500000000, 'excerpt' => 'Sedan listrik luxury jarak tempuh tinggi.'],
        ['name' => 'Gravity', 'body_type' => 'EV', 'price_from' => 2800000000, 'excerpt' => 'SUV listrik premium Lucid.'],
    ],
    'yamaha' => [
        ['name' => 'NMAX', 'slug' => 'nmax', 'body_type' => 'Scooter', 'price_from' => 32575000, 'excerpt' => 'Maxi scooter 155 cc dengan konektivitas.', 'commons' => 'File:Yamaha NMAX 155.jpg'],
        ['name' => 'Aerox', 'body_type' => 'Scooter', 'price_from' => 28975000, 'excerpt' => 'Skutik sporty Yamaha untuk anak muda.'],
        ['name' => 'MT-15', 'body_type' => 'Naked', 'price_from' => 39975000, 'excerpt' => 'Naked bike agresif berbasis R15.'],
        ['name' => 'R15', 'body_type' => 'Sport', 'price_from' => 43975000, 'excerpt' => 'Sportbike entry dengan fairing balap.'],
    ],
    'kawasaki' => [
        ['name' => 'Ninja ZX-25R', 'slug' => 'ninja-zx-25r', 'body_type' => 'Sport', 'price_from' => 112000000, 'excerpt' => 'Sportbike 4-silinder 250 cc ikonik.'],
        ['name' => 'W175', 'body_type' => 'Classic', 'price_from' => 36000000, 'excerpt' => 'Motor klasik bergaya retro Kawasaki.'],
        ['name' => 'KLX 150', 'slug' => 'klx-150', 'body_type' => 'Trail', 'price_from' => 40000000, 'excerpt' => 'Trail ringan untuk petualangan.'],
    ],
    'ducati' => [
        ['name' => 'Panigale V2', 'slug' => 'panigale-v2', 'body_type' => 'Sport', 'price_from' => 650000000, 'excerpt' => 'Superbike Italia untuk track dan jalan.'],
        ['name' => 'Monster', 'body_type' => 'Naked', 'price_from' => 420000000, 'excerpt' => 'Naked bike ikonik Ducati.'],
        ['name' => 'Multistrada V4', 'slug' => 'multistrada-v4', 'body_type' => 'Adventure', 'price_from' => 850000000, 'excerpt' => 'Adventure tourer performa tinggi.'],
    ],
    'harley-davidson' => [
        ['name' => 'Sportster S', 'slug' => 'sportster-s', 'body_type' => 'Cruiser', 'price_from' => 550000000, 'excerpt' => 'Cruiser modern berkarakter Harley.'],
        ['name' => 'Street Glide', 'slug' => 'street-glide', 'body_type' => 'Touring', 'price_from' => 900000000, 'excerpt' => 'Touring bagger klasik Amerika.'],
    ],
    'ktm' => [
        ['name' => '390 Duke', 'slug' => '390-duke', 'body_type' => 'Naked', 'price_from' => 105000000, 'excerpt' => 'Naked bike lincah Ready to Race.'],
        ['name' => '390 Adventure', 'slug' => '390-adventure', 'body_type' => 'Adventure', 'price_from' => 125000000, 'excerpt' => 'Adventure entry untuk touring ringan.'],
    ],
    'triumph' => [
        ['name' => 'Trident 660', 'slug' => 'trident-660', 'body_type' => 'Naked', 'price_from' => 280000000, 'excerpt' => 'Naked triple modern Triumph.'],
        ['name' => 'Tiger 900', 'slug' => 'tiger-900', 'body_type' => 'Adventure', 'price_from' => 450000000, 'excerpt' => 'Adventure midsize untuk touring jauh.'],
    ],
    'royal-enfield' => [
        ['name' => 'Classic 350', 'slug' => 'classic-350', 'body_type' => 'Classic', 'price_from' => 110000000, 'excerpt' => 'Motor klasik Inggris yang ikonik.'],
        ['name' => 'Hunter 350', 'slug' => 'hunter-350', 'body_type' => 'Classic', 'price_from' => 95000000, 'excerpt' => 'Roadster urban Royal Enfield.'],
        ['name' => 'Himalayan', 'body_type' => 'Adventure', 'price_from' => 125000000, 'excerpt' => 'Adventure untuk jalan dan trail.'],
    ],
    'vespa' => [
        ['name' => 'Sprint 150', 'slug' => 'sprint-150', 'body_type' => 'Scooter', 'price_from' => 58000000, 'excerpt' => 'Skuter Italia stylish untuk kota.'],
        ['name' => 'GTS 150', 'slug' => 'gts-150', 'body_type' => 'Scooter', 'price_from' => 85000000, 'excerpt' => 'Maxi scooter premium Vespa.'],
        ['name' => 'Primavera', 'body_type' => 'Scooter', 'price_from' => 56000000, 'excerpt' => 'Skuter klasik elegan Vespa.'],
    ],
    'piaggio' => [
        ['name' => 'Medley 150', 'slug' => 'medley-150', 'body_type' => 'Scooter', 'price_from' => 48000000, 'excerpt' => 'Skutik premium praktis Piaggio.'],
        ['name' => 'Beverly 400', 'slug' => 'beverly-400', 'body_type' => 'Scooter', 'price_from' => 160000000, 'excerpt' => 'Maxi scooter touring Piaggio.'],
    ],
    'aprilia' => [
        ['name' => 'RS 457', 'slug' => 'rs-457', 'body_type' => 'Sport', 'price_from' => 180000000, 'excerpt' => 'Sportbike middleweight Aprilia.'],
        ['name' => 'Tuono 457', 'slug' => 'tuono-457', 'body_type' => 'Naked', 'price_from' => 175000000, 'excerpt' => 'Naked sporty sibling RS 457.'],
    ],
    'benelli' => [
        ['name' => 'TNT 150', 'slug' => 'tnt-150', 'body_type' => 'Naked', 'price_from' => 28000000, 'excerpt' => 'Naked entry terjangkau Benelli.'],
        ['name' => 'TRK 251', 'slug' => 'trk-251', 'body_type' => 'Adventure', 'price_from' => 55000000, 'excerpt' => 'Adventure ringan untuk pemula.'],
    ],
    'tvs' => [
        ['name' => 'Apache RTR 160', 'slug' => 'apache-rtr-160', 'body_type' => 'Sport', 'price_from' => 30000000, 'excerpt' => 'Sport naked entry TVS.'],
        ['name' => 'Ntorq 125', 'slug' => 'ntorq-125', 'body_type' => 'Scooter', 'price_from' => 23000000, 'excerpt' => 'Skutik sporty TVS.'],
    ],
    'bajaj' => [
        ['name' => 'Pulsar NS200', 'slug' => 'pulsar-ns200', 'body_type' => 'Naked', 'price_from' => 38000000, 'excerpt' => 'Naked sporty Bajaj.'],
        ['name' => 'Dominar 400', 'slug' => 'dominar-400', 'body_type' => 'Sport', 'price_from' => 75000000, 'excerpt' => 'Touring sport Bajaj berkarakter tegas.'],
    ],
    'hero' => [
        ['name' => 'Xpulse 200', 'slug' => 'xpulse-200', 'body_type' => 'Adventure', 'price_from' => 40000000, 'excerpt' => 'Adventure ringan Hero.'],
        ['name' => 'Destini 125', 'slug' => 'destini-125', 'body_type' => 'Scooter', 'price_from' => 20000000, 'excerpt' => 'Skutik keluarga Hero.'],
    ],
    'husqvarna' => [
        ['name' => 'Svartpilen 401', 'slug' => 'svartpilen-401', 'body_type' => 'Naked', 'price_from' => 120000000, 'excerpt' => 'Scrambler-naked bergaya Nordic.'],
        ['name' => 'Norden 901', 'slug' => 'norden-901', 'body_type' => 'Adventure', 'price_from' => 450000000, 'excerpt' => 'Adventure mid-weight Husqvarna.'],
    ],
    'mv-agusta' => [
        ['name' => 'F3', 'slug' => 'mv-f3', 'body_type' => 'Sport', 'price_from' => 700000000, 'excerpt' => 'Superbike Italia eksklusif MV Agusta.'],
        ['name' => 'Brutale', 'body_type' => 'Naked', 'price_from' => 650000000, 'excerpt' => 'Naked agresif khas MV Agusta.'],
    ],
    'indian' => [
        ['name' => 'Scout', 'body_type' => 'Cruiser', 'price_from' => 550000000, 'excerpt' => 'Cruiser Amerika modern Indian.'],
        ['name' => 'Chief', 'body_type' => 'Cruiser', 'price_from' => 700000000, 'excerpt' => 'Cruiser klasik berkarakter Indian.'],
    ],
    'cfmoto' => [
        ['name' => '250 NK', 'slug' => '250-nk', 'body_type' => 'Naked', 'price_from' => 45000000, 'excerpt' => 'Naked entry stylish CFMoto.'],
        ['name' => '450 MT', 'slug' => '450-mt', 'body_type' => 'Adventure', 'price_from' => 110000000, 'excerpt' => 'Adventure midsize CFMoto.'],
    ],
    'keeway' => [
        ['name' => 'K-Light 202', 'slug' => 'k-light-202', 'body_type' => 'Cruiser', 'price_from' => 32000000, 'excerpt' => 'Cruiser entry Keeway.'],
        ['name' => 'Vieste 300', 'slug' => 'vieste-300', 'body_type' => 'Scooter', 'price_from' => 48000000, 'excerpt' => 'Maxi scooter Keeway.'],
    ],
    'sym' => [
        ['name' => 'Jet X 150', 'slug' => 'jet-x-150', 'body_type' => 'Scooter', 'price_from' => 35000000, 'excerpt' => 'Skutik sporty SYM.'],
        ['name' => 'Cruisym 250', 'slug' => 'cruisym-250', 'body_type' => 'Scooter', 'price_from' => 65000000, 'excerpt' => 'Maxi scooter nyaman SYM.'],
    ],
    'kymco' => [
        ['name' => 'X-Town 250', 'slug' => 'x-town-250', 'body_type' => 'Scooter', 'price_from' => 70000000, 'excerpt' => 'Maxi scooter touring Kymco.'],
        ['name' => 'KRV 200', 'slug' => 'krv-200', 'body_type' => 'Scooter', 'price_from' => 55000000, 'excerpt' => 'Skutik sporty Kymco.'],
    ],
    'viar' => [
        ['name' => 'Q1', 'slug' => 'viar-q1', 'body_type' => 'EV', 'price_from' => 18000000, 'excerpt' => 'Motor listrik lokal Viar untuk harian.'],
        ['name' => 'N2', 'slug' => 'viar-n2', 'body_type' => 'EV', 'price_from' => 22000000, 'excerpt' => 'Skutik listrik Viar bergaya modern.'],
    ],
    'gesits' => [
        ['name' => 'G1', 'slug' => 'gesits-g1', 'body_type' => 'EV', 'price_from' => 30000000, 'excerpt' => 'Motor listrik nasional Gesits.'],
        ['name' => 'Raya', 'slug' => 'gesits-raya', 'body_type' => 'EV', 'price_from' => 28000000, 'excerpt' => 'Skutik listrik Gesits untuk kota.'],
    ],
    'alva' => [
        ['name' => 'One', 'slug' => 'alva-one', 'body_type' => 'EV', 'price_from' => 45000000, 'excerpt' => 'Motor listrik premium lokal Alva.'],
        ['name' => 'Cervo', 'body_type' => 'EV', 'price_from' => 55000000, 'excerpt' => 'Skutik listrik stylish Alva.'],
    ],
    'polytron' => [
        ['name' => 'Fox-R', 'slug' => 'fox-r', 'body_type' => 'EV', 'price_from' => 25000000, 'excerpt' => 'Motor listrik Polytron untuk komuter.'],
        ['name' => 'T-Rex', 'slug' => 't-rex', 'body_type' => 'EV', 'price_from' => 28000000, 'excerpt' => 'Skutik listrik Polytron.'],
    ],
    'selis' => [
        ['name' => 'Agats', 'body_type' => 'EV', 'price_from' => 20000000, 'excerpt' => 'Motor listrik Selis untuk harian.'],
        ['name' => 'E-Max', 'slug' => 'selis-e-max', 'body_type' => 'EV', 'price_from' => 23000000, 'excerpt' => 'Skutik listrik Selis.'],
    ],
    'smoot' => [
        ['name' => 'Tempur', 'body_type' => 'EV', 'price_from' => 22000000, 'excerpt' => 'Motor listrik Smoot bergaya sporty.'],
        ['name' => 'Zuzu', 'body_type' => 'EV', 'price_from' => 19000000, 'excerpt' => 'Skutik listrik ringkas Smoot.'],
    ],
    'uwinfly' => [
        ['name' => 'T3', 'slug' => 'uwinfly-t3', 'body_type' => 'EV', 'price_from' => 15000000, 'excerpt' => 'Motor listrik terjangkau Uwinfly.'],
        ['name' => 'D5', 'slug' => 'uwinfly-d5', 'body_type' => 'EV', 'price_from' => 17000000, 'excerpt' => 'Skutik listrik Uwinfly.'],
    ],
    'volta' => [
        ['name' => '401', 'slug' => 'volta-401', 'body_type' => 'EV', 'price_from' => 20000000, 'excerpt' => 'Motor listrik Volta untuk kota.'],
        ['name' => '606', 'slug' => 'volta-606', 'body_type' => 'EV', 'price_from' => 24000000, 'excerpt' => 'Skutik listrik Volta.'],
    ],
    'electrum' => [
        ['name' => 'H5', 'slug' => 'electrum-h5', 'body_type' => 'EV', 'price_from' => 25000000, 'excerpt' => 'Motor listrik Electrum.'],
        ['name' => 'S5', 'slug' => 'electrum-s5', 'body_type' => 'EV', 'price_from' => 27000000, 'excerpt' => 'Skutik listrik Electrum.'],
    ],
    'smk' => [
        ['name' => 'E-Bike Urban', 'slug' => 'smk-urban', 'body_type' => 'EV', 'price_from' => 12000000, 'excerpt' => 'Sepeda listrik urban SMK.'],
        ['name' => 'E-Scooter', 'slug' => 'smk-escooter', 'body_type' => 'EV', 'price_from' => 15000000, 'excerpt' => 'Skuter listrik ringkas SMK.'],
    ],
    'qjmotor' => [
        ['name' => 'SRK 400', 'slug' => 'srk-400', 'body_type' => 'Naked', 'price_from' => 85000000, 'excerpt' => 'Naked midsize QJMotor.'],
        ['name' => 'SRV 550', 'slug' => 'srv-550', 'body_type' => 'Scooter', 'price_from' => 120000000, 'excerpt' => 'Maxi scooter QJMotor.'],
    ],
    'zontes' => [
        ['name' => '350R', 'slug' => 'zontes-350r', 'body_type' => 'Sport', 'price_from' => 95000000, 'excerpt' => 'Sportbike midsize Zontes.'],
        ['name' => '350D', 'slug' => 'zontes-350d', 'body_type' => 'Adventure', 'price_from' => 98000000, 'excerpt' => 'Adventure midsize Zontes.'],
    ],
    'gpx' => [
        ['name' => 'Demon 150 GR', 'slug' => 'demon-150-gr', 'body_type' => 'Sport', 'price_from' => 32000000, 'excerpt' => 'Sport entry GPX.'],
        ['name' => 'Racing Boy', 'slug' => 'racing-boy', 'body_type' => 'Naked', 'price_from' => 28000000, 'excerpt' => 'Naked sporty GPX.'],
    ],
    'italjet' => [
        ['name' => 'Dragster 200', 'slug' => 'dragster-200', 'body_type' => 'Scooter', 'price_from' => 120000000, 'excerpt' => 'Skuter unik Italia Italjet.'],
        ['name' => 'Dragster 125', 'slug' => 'dragster-125', 'body_type' => 'Scooter', 'price_from' => 95000000, 'excerpt' => 'Versi entry Dragster Italjet.'],
    ],
    'lambretta' => [
        ['name' => 'V125 Special', 'slug' => 'v125-special', 'body_type' => 'Scooter', 'price_from' => 65000000, 'excerpt' => 'Skuter klasik modern Lambretta.'],
        ['name' => 'X300', 'slug' => 'lambretta-x300', 'body_type' => 'Scooter', 'price_from' => 110000000, 'excerpt' => 'Maxi scooter premium Lambretta.'],
    ],
    'zero' => [
        ['name' => 'SR/F', 'slug' => 'zero-srf', 'body_type' => 'EV', 'price_from' => 550000000, 'excerpt' => 'Naked listrik performa Zero.'],
        ['name' => 'DSR/X', 'slug' => 'zero-dsrx', 'body_type' => 'Adventure', 'price_from' => 600000000, 'excerpt' => 'Adventure listrik Zero Motorcycles.'],
    ],
    'energica' => [
        ['name' => 'Ego', 'slug' => 'energica-ego', 'body_type' => 'EV', 'price_from' => 900000000, 'excerpt' => 'Electric superbike Italia Energica.'],
        ['name' => 'Experia', 'body_type' => 'Adventure', 'price_from' => 850000000, 'excerpt' => 'Electric adventure Energica.'],
    ],
];
