<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run()
    {
        // Common Specifications Group
        $commonGroup = AttributeGroup::create([
            'name' => 'Common Specifications',
            'position' => 1
        ]);

        // Create common attributes
        Attribute::create([
            'attribute_group_id' => $commonGroup->id,
            'name' => 'Brand',
            'type' => 'select',
            'position' => 1,
            'required' => true,
            'filterable' => true,
            'options' => ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'Lenovo', 'HP', 'Dell', 'Asus']
        ]);

        Attribute::create([
            'attribute_group_id' => $commonGroup->id,
            'name' => 'Color',
            'type' => 'select',
            'position' => 2,
            'required' => true,
            'filterable' => true,
            'options' => ['Black', 'White', 'Silver', 'Gold', 'Blue', 'Red']
        ]);

        // Device Specifications Group
        $deviceGroup = AttributeGroup::create([
            'name' => 'Device Specifications',
            'position' => 2
        ]);

        // Create device attributes
        Attribute::create([
            'attribute_group_id' => $deviceGroup->id,
            'name' => 'Display Size',
            'type' => 'text',
            'position' => 1,
            'required' => true,
            'filterable' => true
        ]);

        Attribute::create([
            'attribute_group_id' => $deviceGroup->id,
            'name' => 'Display Resolution',
            'type' => 'text',
            'position' => 2,
            'required' => true,
            'filterable' => false
        ]);

        Attribute::create([
            'attribute_group_id' => $deviceGroup->id,
            'name' => 'Processor',
            'type' => 'text',
            'position' => 3,
            'required' => true,
            'filterable' => true
        ]);

        Attribute::create([
            'attribute_group_id' => $deviceGroup->id,
            'name' => 'RAM',
            'type' => 'select',
            'position' => 4,
            'required' => true,
            'filterable' => true,
            'options' => ['4GB', '6GB', '8GB', '12GB', '16GB', '32GB']
        ]);

        Attribute::create([
            'attribute_group_id' => $deviceGroup->id,
            'name' => 'Storage',
            'type' => 'select',
            'position' => 5,
            'required' => true,
            'filterable' => true,
            'options' => ['64GB', '128GB', '256GB', '512GB', '1TB']
        ]);

        // Camera Specifications Group
        $cameraGroup = AttributeGroup::create([
            'name' => 'Camera',
            'position' => 3
        ]);

        // Create camera attributes
        Attribute::create([
            'attribute_group_id' => $cameraGroup->id,
            'name' => 'Main Camera',
            'type' => 'text',
            'position' => 1,
            'required' => false,
            'filterable' => false
        ]);

        Attribute::create([
            'attribute_group_id' => $cameraGroup->id,
            'name' => 'Front Camera',
            'type' => 'text',
            'position' => 2,
            'required' => false,
            'filterable' => false
        ]);

        // Battery & Power Group
        $batteryGroup = AttributeGroup::create([
            'name' => 'Battery & Power',
            'position' => 4
        ]);

        // Create battery attributes
        Attribute::create([
            'attribute_group_id' => $batteryGroup->id,
            'name' => 'Battery Capacity',
            'type' => 'text',
            'position' => 1,
            'required' => true,
            'filterable' => true
        ]);

        Attribute::create([
            'attribute_group_id' => $batteryGroup->id,
            'name' => 'Fast Charging',
            'type' => 'boolean',
            'position' => 2,
            'required' => true,
            'filterable' => true
        ]);

        // Additional Features Group
        $featuresGroup = AttributeGroup::create([
            'name' => 'Additional Features',
            'position' => 5
        ]);

        // Create additional features
        Attribute::create([
            'attribute_group_id' => $featuresGroup->id,
            'name' => '5G Support',
            'type' => 'boolean',
            'position' => 1,
            'required' => true,
            'filterable' => true
        ]);

        Attribute::create([
            'attribute_group_id' => $featuresGroup->id,
            'name' => 'NFC',
            'type' => 'boolean',
            'position' => 2,
            'required' => true,
            'filterable' => true
        ]);

        Attribute::create([
            'attribute_group_id' => $featuresGroup->id,
            'name' => 'Wireless Charging',
            'type' => 'boolean',
            'position' => 3,
            'required' => true,
            'filterable' => true
        ]);

        Attribute::create([
            'attribute_group_id' => $featuresGroup->id,
            'name' => 'Water Resistance',
            'type' => 'text',
            'position' => 4,
            'required' => false,
            'filterable' => true
        ]);
    }
}
