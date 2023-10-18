<?php
/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

require_once 'lib/package.class.php';
$Package = new Package($DB);

# pagination
$TemplateData['pagination'] = array('pages' => 0, 'currentGetParameters' => array('p' => 'package'));

$_curPage = 1;
if(isset($_GET['page']) && !empty($_GET['page'])) {
	$_curPage = trim($_GET['page']);
	$_curPage = Helper::validate($_curPage,'digit') ? $_curPage : 1;
}

$_sort = 'default';
if(isset($_GET['s']) && !empty($_GET['s'])) {
	$_sort = trim($_GET['s']);
	$_sort = Helper::validate($_sort,'nospace') ? $_sort : 'default';
}

$_sortDirection = '';
if(isset($_GET['sd']) && !empty($_GET['sd'])) {
	$_sortDirection = trim($_GET['sd']);
	$_sortDirection = Helper::validate($_sortDirection,'nospace') ? $_sortDirection : '';
}

$_rpp = RESULTS_PER_PAGE;
if(isset($_GET['rpp']) && !empty($_GET['rpp'])) {
    $_rpp = trim($_GET['rpp']);
    $_rpp = Helper::validate($_rpp,'digit') ? $_rpp : RESULTS_PER_PAGE;
}

$queryOptions = array(
	'limit' => $_rpp,
	'offset' => ($_rpp * ($_curPage-1)),
	'sort' => $_sort,
	'sortDirection' => $_sortDirection
);
## pagination end

$_id = '';
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Helper::validate($_id,'nospace') ? $_id : '';
}

$TemplateData['pageTitle'] = 'Package details';
$TemplateData['package'] = array();
$TemplateData['files'] = array();
$TemplateData['searchInput'] = '';

if(!empty($_id)) {
	$Package->setQueryOptions($queryOptions);
	$package = $Package->getPackage($_id);
	if(!empty($package)) {
		$TemplateData['package'] = $package;

        // difference between search and no search
        // error messages and additional query params
        $searchValue = '';
        if(isset($_GET['ps'])) {
            $searchValue = trim($_GET['ps']);
            $searchValue = strtolower($searchValue);
            $searchValue = urldecode($searchValue);
        }

        if(!empty($searchValue)) {
            if(Helper::validate($searchValue,'nospaceP')) {
                if($Package->prepareSearchValue($searchValue)) {
                    $TemplateData['files'] = $Package->getPackageFiles($_id);

                    if(empty($TemplateData['files'])) {
                        $messageData['status'] = "warning";
                        $messageData['message'] = "Nothing found for this criteria term or the data is not known yet.";
                    }

                    $TemplateData['searchInput'] = htmlspecialchars($searchValue);
                    $TemplateData['pagination']['currentGetParameters']['ps'] = urlencode($searchValue);
                } else {
                    $messageData['status'] = "error";
                    $messageData['message'] = "Invalid search criteria. At least two (without wildcard) chars.";
                }
            } else {
                $messageData['status'] = "error";
                $messageData['message'] = "Invalid search criteria.";
            }
        } else {
            $TemplateData['files'] = $Package->getPackageFiles($_id);
        }

		$TemplateData['pageTitle'] = $package['name'].'/'.$package['categoryName'].' '.$package['version'].' '.$package['arch'];
		$TemplateData['pagination']['currentGetParameters']['id'] = $_id;
	} else {
		$messageData['status'] = "error";
		$messageData['message'] = "Invalid package id";
	}
}

## pagination
if(!empty($TemplateData['files']['amount'])) {
	$TemplateData['pagination']['pages'] = (int)ceil($TemplateData['files']['amount'] / $_rpp);
	$TemplateData['pagination']['curPage'] = $_curPage;

	$TemplateData['pagination']['currentGetParameters']['page'] = $_curPage;
	$TemplateData['pagination']['currentGetParameters']['s'] =  $_sort;
	$TemplateData['pagination']['currentGetParameters']['sd'] = $_sortDirection;
    $TemplateData['pagination']['currentGetParameters']['rpp'] = $_rpp;
    $TemplateData['pagination']['sortOptions'] = $Package->getSortOptions();
}

if($TemplateData['pagination']['pages'] > 11) {
	# first pages
	$TemplateData['pagination']['visibleRange'] = range(1,3);
	# last pages
	foreach(range($TemplateData['pagination']['pages']-2, $TemplateData['pagination']['pages']) as $e) {
		$TemplateData['pagination']['visibleRange'][] = $e;
	}
	# pages before and after current page
	$cRange = range($TemplateData['pagination']['curPage']-1, $TemplateData['pagination']['curPage']+1);
	foreach($cRange as $e) {
		$TemplateData['pagination']['visibleRange'][] = $e;
	}
	$TemplateData['pagination']['currentRangeStart'] = array_shift($cRange);
	$TemplateData['pagination']['currentRangeEnd'] = array_pop($cRange);
}
else {
	$TemplateData['pagination']['visibleRange'] = range(1,$TemplateData['pagination']['pages']);
}
## pagination end
