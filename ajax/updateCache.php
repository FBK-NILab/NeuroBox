<?php

/*
 * updateCache
 * Created on: Jan 22, 2013 4:41:57 PM
 * 
 * Copyright 2013 EnginSoft S.p.A.
 * All rights reserved
 */


//# first, clear the "fast" cache, to make sure that missing DB items are inserted/updated
OC_Cache::clear();

//# then scan the filesystem
OC_FileCache::scan("");

echo 'true';
?>


