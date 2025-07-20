<?php
/**
 * Functions to create base maps from GeoJSON.
 */

/**
 * Invent layer name from GeoJSON properties.
 *
 * @param array $properties GeoJSON properties.
 *
 * @return string Layer name.
 */
function set_layer($properties)
{
    if (isset($properties['building'])) {
        return 'building';
    }

    if (isset($properties['highway'])) {
        return 'highway';
    }

    if (isset($properties['railway'])) {
        return 'railway';
    }

    if (isset($properties['waterway'])) {
        return 'waterway';
    }

    if (isset($properties['landuse'])) {
        return 'landuse_' . $properties['landuse'];
    }

    if (isset($properties['natural'])) {
        return 'natural_' . $properties['natural'];
    }

    return 'other';
}

/**
 * Set order of layers for rendering the map to DXF.
 *
 * @return string[] Layer names.
 */
function layer_order()
{
    $layers = <<<LAYERS
landuse_orchard
landuse_garages
landuse_farmyard
landuse_plant_nursery
landuse_brownfield
landuse_reservoir
landuse_railway
landuse_flowerbed
landuse_forest
landuse_farmland
landuse_meadow
landuse_residential
landuse_quarry
landuse_village_green
landuse_cemetery
landuse_grass
landuse_industrial
landuse_basin
landuse_allotments
landuse_recreation_ground
waterway
natural_water
building
highway
railway
LAYERS;
    return explode(PHP_EOL, $layers);

}
