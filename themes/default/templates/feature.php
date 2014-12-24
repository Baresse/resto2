<?php
    
    /*
     * Set variables
     */
    $product = $self->toArray();  
    
    $_data = '{"type":"FeatureCollection","features":[' . $self->toJSON() . ']}';
    $_issuer = 'getResource';
    
    $thumbnail = $product['properties']['thumbnail'];
    $quicklook = $product['properties']['quicklook'];
    if (!isset($thumbnail) && isset($quicklook)) {
        $thumbnail = $quicklook;
    }
    else if (!isset($thumbnail) && !isset($quicklook)) {
        $thumbnail = $self->context->baseUrl . 'themes/' . $self->context->config['theme'] . '/img/noimage.png';
    }
    if (!isset($quicklook)) {
        $quicklook = $thumbnail;
    }
    
    /*
     * Wikipedia
     */
    if (isset($self->context->config['modules']['Wikipedia'])) {
        $self->wikipedia = new Wikipedia($self->context, $self->user, $self->context->config['modules']['Wikipedia']);
        $wikipediaEntries = $self->wikipedia->search(array(
            'polygon' => RestoUtil::geoJSONGeometryToWKT($product['geometry']),
            'limit' => 10
        ));
    }
    
    if (isset($self->context->config['modules']['PopulationCounter'])) {
        $self->populationCounter = new PopulationCounter($self->context, $self->user, $self->context->config['modules']['PopulationCounter']);
    }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $self->context->dictionary->language ?>">
    <?php include '_head.php' ?>
    <body>
        
        <!-- Header -->
        <?php include '_header.php' ?>
      
        <!-- Collection title and description -->
        <div class="row fullWidth light">
            <div class="large-6 columns center text-dark padded-top">
                <h2><?php echo $self->context->dictionary->translate('_resourceSummary', $product['properties']['platform'], substr($product['properties']['startDate'],0, 10)); ?></h2>
                <h7 title="<?php echo $product['id']; ?>" style="overflow: hidden;"><?php echo $product['id']; ?></h7>
                <?php if (isset($product['properties']['services']) && isset($product['properties']['services']['download']) && isset($product['properties']['services']['download']['url'])) { ?>
                <p class="center padded-top">
                    <?php if ($self->user->canDownload($self->collection->name, $product['id'])) { ?>
                    <a class="fa fa-3x fa-cloud-download downloadProduct" href="<?php echo $product['properties']['services']['download']['url'] . '?lang=' . $self->context->dictionary->language; ?>" <?php echo $product['properties']['services']['download']['mimeType'] === 'text/html' ? 'target="_blank"' : ''; ?> title="<?php echo $self->context->dictionary->translate('_download'); ?>"></a>&nbsp;&nbsp;
                    <?php } ?>
                    <?php if ($self->user->profile['userid'] !== -1) { ?>
                    <a class="fa fa-3x fa-shopping-cart addToCart" href="#" title="<?php echo $self->context->dictionary->translate('_addToCart'); ?>"></a> 
                    <?php } ?>
                </p>
                <?php } ?>
            </div>
            <div class="large-6 columns text-dark padded-top">
                <h3><?php echo $self->collection->osDescription[$self->context->dictionary->language]['ShortName']; ?></h3>
                <p style="text-align:justify">
                    <?php echo $self->collection->osDescription[$self->context->dictionary->language]['Description']; ?>
                </p>
            </div>
        </div>
        
        <!-- mapshup display -->
        <div id="mapshup" class="noResizeHeight"></div>
        
        <!-- Quicklook and metadata -->
        <div class="row resto-resource fullWidth light" style="padding-bottom: 20px;">
            <div class="large-6 columns center">
                <img title="<?php echo $product['id'];?>" class="resto-image" src="<?php echo $quicklook;?>"/>
            </div>
            <div class="large-6 columns">
                <table style="width:100%;">
                    <?php
                    if (isset($product['properties']) && is_array($product['properties'])) {
                        $excluded = array('quicklook', 'thumbnail', 'links', 'services', 'keywords', 'updated', 'productId', 'landUse');
                        foreach(array_keys($product['properties']) as $key) {
                            if (in_array($key, $excluded)) {
                                continue;
                            }
                            if (!is_array($product['properties'][$key])) {
                                echo '<tr><td>' . $self->context->dictionary->translate($key) . '</td><td>' . $product['properties'][$key] . '</td></tr>';
                            }   
                        }
                    }
                    ?>
                </table>
            </div>
        </div>
        
        <!-- Location content -->
        <div class="row resto-resource fullWidth dark">
            <div class="large-6 columns">
                <h1><span class="right"><?php echo $self->context->dictionary->translate('_location'); ?></span></h1>
            </div>
            <div class="large-6 columns">
            <?php
            foreach(array_values(array('continent', 'country', 'region', 'state')) as $key) {
                if (isset($product['properties']['keywords'])) {
                        for ($i = 0, $l = count($product['properties']['keywords']); $i < $l; $i++) {
                            list($type, $id) = explode(':', $product['properties']['keywords'][$i]['id'], 2);
                            if (strtolower($type) === $key && $product['properties']['keywords'][$i]['id'] !== 'region:_all') { ?>
                <h2><a title="<?php echo $self->context->dictionary->translate('_thisResourceIsLocated', $product['properties']['keywords'][$i]['name']) ?>" href="<?php echo RestoUtil::updateUrlFormat($product['properties']['keywords'][$i]['href'], 'html') ?>"><?php echo $product['properties']['keywords'][$i]['name']; ?></a></h2>
            <?php }}}} ?>
            </div>
        </div>
        
        <!-- Thematic content (Landcover) -->
        <div class="row resto-resource fullWidth light">
            <div class="large-6 columns">
                <h1><span class="right"><?php echo $self->context->dictionary->translate('_landUse'); ?></span></h1>
            </div>
            <div class="large-6 columns">
            <?php
                    if (isset($product['properties']['keywords'])) {
                        for ($i = 0, $l = count($product['properties']['keywords']); $i < $l; $i++) {
                            list($type, $id) = explode(':', $product['properties']['keywords'][$i]['id'], 2);
                            if (strtolower($type) === 'landuse') { ?>
                    <h2><?php echo round($product['properties']['keywords'][$i]['value']); ?> % <a title="<?php echo $self->context->dictionary->translate('_thisResourceContainsLanduse', $product['properties']['keywords'][$i]['value'], $product['properties']['keywords'][$i]['name']) ?>" href="<?php echo RestoUtil::updateUrlFormat($product['properties']['keywords'][$i]['href'], 'html') ?>"><?php echo $product['properties']['keywords'][$i]['name']; ?></a></h2>
            <?php }}} ?>
            </div>
        </div>
        
        <!-- Population counter -->
        <?php if (isset($self->populationCounter)) { ?>
        <div class="row resto-resource fullWidth dark">
            <div class="large-6 columns">
                <h1 class="right"><?php echo $self->context->dictionary->translate('_estimatedPopulation'); ?></h1>
            </div>
            <div class="large-6 columns">
                <h2 class="text-light"><?php echo $self->context->dictionary->translate('_people', $self->populationCounter->count($product['geometry'])) ?></h2>
            </div>
        </div>
        <?php } ?>
        
        <!-- Wikipedia -->
        <?php if (isset($wikipediaEntries) && is_array($wikipediaEntries) && count($wikipediaEntries) > 0) { ?>
        <div class="row resto-resource fullWidth light">
            <div class="large-6 columns">
                <h1 class="right"><?php echo $self->context->dictionary->translate('_poi'); ?></h1>
            </div>
            <div class="large-6 columns">
                <?php foreach ($wikipediaEntries as $wikipediaEntry) { ?>
                <h2><a href="<?php echo $wikipediaEntry['url']; ?>"><?php echo $wikipediaEntry['title']; ?></a></h2>
                <p><?php echo $wikipediaEntry['summary']; ?></p>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
        
        <!-- Footer -->
        <?php include '_footer.php' ?>
        
        <!-- scripts -->
        <?php include '_scripts.php' ?>
        <script type="text/javascript">
            $(document).ready(function(){
                $('.downloadProduct').click(function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    return Resto.download($(this));
                });
                $('.addToCart').click(function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    Resto.addToCart(Resto.features['<?php echo $product['id']; ?>']);
                    return false;
                });
            });
        </script>
    </body>
</html>
