<?php
	include_once '../includes/variables.php';
	include_once '../includes/race_class.php';
	include_once '../includes/finder_class.php';

	////////////////////////////////////////////////////////////////////
	/* Nearby Events Postcode *///////////////////////////////////
	// Check for postcode cookie
	$userPC = (isset($_COOKIE["Postcode"])) ? htmlspecialchars($_COOKIE["Postcode"]) : '';

	// Prepare SQL query
	$query = sprintf("SELECT * FROM atr_races WHERE date >= '%s'",
							$queryDate);
	
	//////////////////////////////////////////////////////////////////////////////////////////////
	/* Query Filters *//////////////////////////////////////////////////////////////////////////
	$queryFilters = $regionFilter = $monthFilter = $distanceFilter = $surfaceFilter = '';
	/* Region Filters *////////////////////////////////////////////
	if(isset($_GET["region"])){
		if($_GET["region"] == 'North_East'){$regionFilter .= " AND region='A'";}
		elseif($_GET["region"] == 'North_West'){$regionFilter .= " AND region='B'";}
		elseif($_GET["region"] == 'Yorkshire_and_Humber'){$regionFilter .= " AND region='D'";}
		elseif($_GET["region"] == 'Midlands'){$regionFilter .= " AND (region='E' OR region='F')";}
		elseif($_GET["region"] == 'East_England'){$regionFilter .= " AND region='G'";}
		elseif($_GET["region"] == 'London'){$regionFilter .= " AND region='H'";}
		elseif($_GET["region"] == 'South_East'){$regionFilter .= " AND region='J'";}
		elseif($_GET["region"] == 'South_West'){$regionFilter .= " AND region='K'";}
		elseif($_GET["region"] == 'Wales'){$regionFilter .= " AND region='L'";}
		elseif($_GET["region"] == 'Scotland'){$regionFilter .= " AND region='M'";}
		elseif($_GET["region"] == 'Northern_Ireland'){$regionFilter .= " AND region='N'";}
		elseif($_GET["region"] == 'Undefined'){$regionFilter .= " AND region='none'";}
		$queryFilters .= $regionFilter;
	}

	/* Month Filters *//////////////////////////////////////////////
	$monthNames = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	if(isset($_GET["month"]))
	{
		for($i=0; $i<12; $i++)
		{
			if($_GET["month"] == $monthNames[(date('n') - 1 + $i) % 12])
				$monthFilter .= " AND date >= '".date('Y-m-01', strtotime("first day of +".$i." month"))."' AND date < '".date('Y-m-01', strtotime("first day of +".($i+1)." month"))."'";
		}
		$queryFilters .= $monthFilter;
	}

	/* Distance Filters *////////////////////////////////////////////
	if(isset($_GET['distance'])){
		if($_GET['distance'] == '5K'){$distanceFilter .= " AND distance='5km'";}
		elseif($_GET['distance'] == '10K'){$distanceFilter .= " AND distance LIKE '%10km%'";}
		elseif($_GET['distance'] == 'Half_Marathon'){$distanceFilter .= " AND distance LIKE '%13.1 miles%'";}
		elseif($_GET['distance'] == 'Marathon'){$distanceFilter .= " AND distance LIKE '%26.2 miles%'";}
		elseif($_GET['distance'] == 'Other'){$distanceFilter .= " AND distance <> '5km' AND distance <> '10km' AND distance <> '13.1 miles' AND distance <> '26.2 miles'";}
		$queryFilters .= $distanceFilter;
	}

	/* Surface Filters *//////////////////////////////////////////////
	if(isset($_GET['surface'])){
		if($_GET['surface'] == 'Road'){$surfaceFilter .= " AND type='road race'";}
		elseif($_GET['surface'] == 'XC'){$surfaceFilter .= " AND type='cross country'";}
		elseif($_GET['surface'] == 'Multi-Terrain'){$surfaceFilter .= " AND type='multi-terrain'";}
		elseif($_GET['surface'] == 'Track'){$surfaceFilter .= " AND type='track'";}
		elseif($_GET['surface'] == 'Other'){$surfaceFilter .= " AND type <> 'road race' AND type <> 'cross country' AND type <> 'multi-terrain' AND type <> 'track'";}
		$queryFilters .= $surfaceFilter;
	}

	$query .= $queryFilters." ORDER BY `atr_races`.`date`";
	$result = $connection->query($query);


	/////////////////////////////////////////////////////////////
	// Filte Badges ///////////////////////////////////////////
	////////////////////////////////////////////////////////////
	// Region Badges Logic
	$regionQuery = "SELECT
		COUNT(*) 'All',
		SUM(region='A') 'North East',
		SUM(region='B') 'North West',
		SUM(region='D') 'Yorkshire and Humber',
		SUM(region='E' OR region='F') 'Midlands',
		SUM(region='G') 'East England',
		SUM(region='H') 'London',
		SUM(region='J') 'South East',
		SUM(region='K') 'South West',
		SUM(region='L') 'Wales',
		SUM(region='M') 'Scotland',
		SUM(region='N') 'Northern Ireland',
		SUM(region='none') 'Undefined'
		FROM atr_races
		WHERE date >= '$queryDate'".$monthFilter.$distanceFilter.$surfaceFilter.";";

	$regionResult = $connection->query($regionQuery);
	$regionCount = $regionResult->fetch();

	$regionNames = [];
	foreach(range(0, $regionResult->columnCount() - 1) as $regionColumns)
		$regionMeta[] = $regionResult->getColumnMeta($regionColumns);
	foreach($regionMeta as $regionColumn)
		array_push($regionNames, $regionColumn['name']);
	
	$regionLinks = array('All', 'North_East', 'North_West', 'Yorkshire_and_Humber', 'Midlands', 'East_England', 'London', 'South_East', 'South_West', 'Wales', 'Scotland', 'Northern_Ireland', 'Undefined');

	// Distance Badges Logic
	$distanceQuery = "SELECT
		COUNT(*) 'All',
		SUM(distance='5km') '5K',
		SUM(distance LIKE '%10km%') '10K',
		SUM(distance LIKE '%13.1 miles%') 'Half Marathon',
		SUM(distance LIKE '%26.2 miles%') 'Marathon',
		SUM(distance<>'5km' AND distance<>'10km' AND distance<>'13.1 miles' AND distance<>'26.2 miles') 'Other'
		FROM atr_races
		WHERE date >= '$queryDate'".$regionFilter.$monthFilter.$surfaceFilter.";";

	$distanceResult = $connection->query($distanceQuery);
	$distanceCount = $distanceResult->fetch();

	$distanceNames = [];
	foreach(range(0, $distanceResult->columnCount() - 1) as $column_index)
		$meta[] = $distanceResult->getColumnMeta($column_index);
	foreach($meta as $column)
		array_push($distanceNames, $column['name']);

	$distanceLinks = array('All', '5K', '10K', 'Half_Marathon', 'Marathon', 'Other');

	// Month Badges Logic
	$monthCount = [];
	for($i=0; $i<12; $i++)
	{
		$monthQuery = "SELECT name FROM atr_races WHERE date >= '$queryDate' AND date >= '".date('Y-m-01', strtotime("first day of +".$i." month"))."' AND date < '".date('Y-m-01', strtotime("first day of +".($i+1)." month"))."'".$regionFilter.$distanceFilter.$surfaceFilter.";";
		$monthResult = $connection->query($monthQuery);
		array_push($monthCount, $monthResult->rowCount());
	}

	// Surface Badges Logic
	$surfaceQuery = "SELECT
		COUNT(*) 'All',
		SUM(type='road race') 'Road',
		SUM(type='Cross Country') 'XC',
		SUM(type='Multi-Terrain') 'Multi-Terrain',
		SUM(type='Track') 'Track',
		SUM(type<>'road race' AND type<>'Cross Country' AND type<>'Multi-Terrain') 'Other'
		FROM atr_races
		WHERE date >= '$queryDate'".$regionFilter.$monthFilter.$distanceFilter.";";

	$surfaceResult = $connection->query($surfaceQuery);
	$surfaceCount = $surfaceResult->fetch();

	$surfaceNames = [];
	foreach(range(0, $surfaceResult->columnCount() - 1) as $surfaceColumns)
		$surfaceMeta[] = $surfaceResult->getColumnMeta($surfaceColumns);
	foreach($surfaceMeta as $surfaceColumn)
		array_push($surfaceNames, $surfaceColumn['name']);


	//////////////////////////////////////////////
	// Page Variable Links ////////////////////
	$and = $linkVariables = $regionVariable = $distanceVariable = $monthVariable = $surfaceVariable = '';
	$rAnd = $dAnd = $mAnd = $sAnd = '';

	if(isset($_GET["region"]))
	{
		$regionVariable .= 'region='.htmlspecialchars($_GET["region"]);
		$linkVariables .= ($linkVariables) ? '&amp;'.$regionVariable : $regionVariable;
		$rAnd = '&amp;';
	}
	else
		$rAnd = '';

	if(isset($_GET["distance"]))
	{
		$distanceVariable .= 'distance='.htmlspecialchars($_GET["distance"]);
		$linkVariables .= ($linkVariables) ? '&amp;'.$distanceVariable : $distanceVariable;
		$dAnd = '&amp;';
	}
	else
		$dAnd = '';

	if(isset($_GET["month"]))
	{
		$monthVariable .= 'month='.htmlspecialchars($_GET["month"]);
		$linkVariables .= ($linkVariables) ? '&amp;'.$monthVariable : $monthVariable;
		$mAnd = '&amp;';
	}
	else
		$mAnd = '';

	if(isset($_GET['surface']))
	{
		$surfaceVariable .= 'surface='.htmlspecialchars($_GET['surface']);
		$linkVariables .= ($linkVariables) ? '&amp;'.$surfaceVariable : $surfaceVariable;
		$sAnd = '&amp;';
	}
	else
		$sAnd = '';

	$and = ($linkVariables) ? '&amp;' : '';


	////////////////////////////////////////////
	// Pagination Logic //////////////////////
	$resultCount = $result->rowCount();

	// Check if page is selected and numerical
	(isset($_GET["pageNo"])) ? $pageNo = preg_replace('#[^0-9]#i', '', $_GET["pageNo"]) : $pageNo = 1;

	// Calculate the amount of pages
	$lastPage = ceil($resultCount / $itemsPerPage);

	// Keep page number within page limits
	($pageNo < 1) ? $pageNo = 1 : (($pageNo > $lastPage) ? $pageNo = $lastPage : '');

	// Pagination buttons
	$centrePage = $nextPage = $prevPage = '';
	$backOnePage = $pageNo - 1;
	$nextOnePage = $pageNo + 1;

	if($pageNo == 1)
		$nextPage = '<a id="next-page-button">'.$nextOnePage.'</a>';
	else if($pageNo == $lastPage)
		$prevPage = '<a id="prev-page-button">'.$backOnePage.'</a>';
	else if($pageNo > 1 && $pageNo < $lastPage)
	{
		$prevPage = '<a id="prev-page-button">'.$backOnePage.'</a>';
		$nextPage = '<a id="next-page-button">'.$nextOnePage.'</a>';
	}

	$limit = ' LIMIT '.($pageNo - 1) * $itemsPerPage.','.$itemsPerPage;
	$limitedResult = $connection->query($query.$limit);
	$limitedResult->setFetchMode(PDO::FETCH_CLASS, 'Race');

	$backButton = ($pageNo != 1) ? '<a><i class="glyphicon glyphicon-chevron-left"></i></a>' : '';
	$nextButton = ($pageNo != $lastPage) ? '<a><i class="glyphicon glyphicon-chevron-right"></i></a>' : '';

	// Page status
	$pageStatus = ($lastPage != 1) ? '<span class="page-status pull-left">Page <span class="page-number">'.$pageNo.'</span> of '.$lastPage.'</span>' : '';
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	// Featured query and result
	$featuredQuery = "SELECT * FROM atr_races WHERE featured AND date >= '$queryDate'";
	$featuredResult = $connection->query($featuredQuery);

	// Debug
	if(DEBUG ==1)
	{
		echo 'D: '.$dAnd.'<br>';
		echo 'M: '.$mAnd.'<br>';
		echo 'R: '.$rAnd.'<br>';
		echo 'S: '.$sAnd.'<br>';
		echo $_SERVER["HTTP_HOST"];
		echo $_SERVER['REQUEST_URI'];
		echo BASE_URL;
		echo $query;
		echo '<pre>', print_r($_GET), '</pre>';
	}
?>

<div id="content">
	<div class="container clearfix">
		<span id="contact-confirmation"></span>

		<div class="row">
			<div class="col-md-4 col-xs-12 pull-right">
				<!--######################################################################-->
				<!-- Find Races Panel ###########################################################-->
				<?php
					(isset($_GET["units"])) ? $units = $_GET["units"] : $units = ' miles';

					$race_finder_panel = new RaceFinder($units, $date, $yearAhead);
					echo $race_finder_panel->get_panel();
				?>
				<!-- End Find Races Panel -->
			</div>

			<!-- Left Column Panel -->
			<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12 pull-left">

				<!-- Featured Race Panel ###################################################-->
				<?php
					if($featuredResult->rowCount())
					{
						$i = 0;

						echo '<div class="panel visible-lg visible-md">
									<div id="featured-carousel" class="carousel slide" data-ride="carousel">
										<div class="carousel-inner"  role="listbox">';

						while($row = $featuredResult->fetch())
						{
							(!$i) ? $active = 'active' : $active = '';
							echo '<div class="item '.$active.'">
										<img src="'.BASE_URL.'/img/featured/'.$row[3].'.jpg" alt="'.$row[1].'">
										<div class="carousel-caption">
											<span class="carousel-text"><a href="race/'.$row[3].'/'.rawurlencode(str_replace(' ', '-', str_replace('/', '-', $row[1]))).'">'.$row[1].'</a></span>
										</div>
									</div>';
							$i++;
						}
						echo '</div>';

						// Featured panel arrows
						if($featuredResult->rowCount() > 1)
							echo '<a class="carousel-control left" href="#featured-carousel" role="button" data-slide="prev">
										<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
										<span class="sr-only">Previous</span>
									</a>
									<a class="carousel-control right" href="#featured-carousel" role="button" data-slide="next">
										<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
										<span class="sr-only">Next</span>
									</a>';

						echo '</div></div>';
					}
				?>
				<!-- End Featured Race Panel ################################################-->

				<!-- Races Panel ##############################################################################################-->
				<div id="races_panel" class="panel">
					<div class="heading">
						<div class="page-header">
							<h3>UK Races
								<ul id="filter-labels">
									<li id="distance-filter-label" class="label label-success" style="display: none;"></li>
									<li id="region-filter-label" class="label label-info" style="display: none;"></li>
									<li id="month-filter-label" class="label label-warning" style="display: none;"></li>
									<li id="surface-filter-label" class="label label-danger" style="display: none;"></li>
								</ul>
								<?php
									///////////////////////////////////////////////////////////////////////////////
									// Filter Labels //////////////////////////////////////////////////////////////
									// Distance
									if(isset($_GET["distance"]))
									{
										$distanceName = htmlspecialchars(str_replace("_", " ", $_GET["distance"]));
										if(in_array($distanceName, $distanceNames) || preg_match('/[0-9]+/', $distanceName))
										{
											if($regionVariable || $monthVariable || $surfaceVariable)
												echo '<a class="label label-success" href="?'.$regionVariable.$mAnd.$monthVariable.$sAnd.$surfaceVariable.'">'.$distanceName.' &times;</a>';
											else
												echo '<a class="label label-success" href="'.BASE_URL.'/">'.$distanceName.' &times;</a>';
										}
									}
									// Region
									if(isset($_GET["region"]))
									{
										$regionName = htmlspecialchars(str_replace("_", " ", $_GET["region"]));
										if(in_array($regionName, $regionNames))
										{
											if($distanceVariable || $monthVariable || $surfaceVariable)
												echo '<a class="label label-info" href="?'.$distanceVariable.$mAnd.$monthVariable.$sAnd.$surfaceVariable.'">'.$regionName.' &times;</a>';
											else
												echo '<a class="label label-info" href="'.BASE_URL.'/">'.$regionName.' &times;</a>';
										}
									}
									// Month
									if(isset($_GET["month"]) && in_array($_GET["month"], $monthNames))
									{
										if($distanceVariable || $regionVariable || $surfaceVariable)
											echo '<a class="label label-warning" href="?'.$distanceVariable.$rAnd.$regionVariable.$sAnd.$surfaceVariable.$mAnd.'">'.htmlspecialchars($_GET["month"]).' &times;</a>';
										else
											echo '<a class="label label-warning" href="'.BASE_URL.'/">'.htmlspecialchars($_GET["month"]).' &times;</a>';
									}
									// Surface
									if(isset($_GET["surface"]) && in_array($_GET["surface"], $surfaceNames))
									{
										if($distanceVariable || $regionVariable || $monthVariable)
											echo '<a class="label label-danger" href="?'.$distanceVariable.$rAnd.$regionVariable.$mAnd.$monthVariable.$sAnd.'">'.htmlspecialchars($_GET["surface"]).' &times;</a>';
										else
											echo '<a class="label label-danger" href="'.BASE_URL.'/">'.htmlspecialchars($_GET["surface"]).' &times;</a>';
									}
								?>

								<!-- Pagination buttons -->
								<?php if($lastPage > 1){ ?>
								<span id="paginationDisplay">
									<div class="pagination pull-right hidden-xs">
										<ul id="pagination">
											<li id="back-button" class="page-back left-arrow"><?= $backButton ?></li>
											<li id="prev-page"><?= $prevPage ?></li>
											<li id="centre-page"><span class="pageActive"><?= $pageNo ?></span></li>
											<li id="next-page"><?= $nextPage ?></li>
											<li id="next-button" class="page-next right-arrow"><?= $nextButton ?></li>
										</ul>
									</div>
								</span>
								<?php } ?>

								<span id="region-filter" style="display: none;"><?= (isset($_GET["region"])) ? $_GET["region"] : '' ?></span>
								<span id="distance-filter" style="display: none;"><?= (isset($_GET["distance"])) ? $_GET["distance"] : '' ?></span>
								<span id="month-filter" style="display: none;"><?= (isset($_GET["month"])) ? $_GET["month"] : '' ?></span>
								<span id="surface-filter" style="display: none;"><?= (isset($_GET["surface"])) ? $_GET["surface"] : '' ?></span>
								<span id="search-query" style="display: none;"><?= (isset($_GET["s"])) ? $_GET["s"] : '' ?></span>
								<span id="from-date" style="display: none;"><?= (isset($_GET["start_date"])) ? $_GET["start_date"] : '' ?></span>
								<span id="to-date" style="display: none;"><?= (isset($_GET["end_date"])) ? $_GET["end_date"] : '' ?></span>
							</h3>
						</div>
					</div>

					<div class="panel-body">
						<!-- Filter Column -->
						<div class="col-md-3 filters visible-lg visible-md">

							<div class="panel panel-default">
								<div class="results-count">
									<!--span id="result-count"-->
										<?=
											$result->rowCount().' race';
											if($result->rowCount() > 1)
												echo 's';
										?>
									<!--/span-->
								</div>
							</div>

							<!--####################################################-->
							<!-- Accordion Filters #########################################-->
							<!-- Distance Filters -->
							<div class="panel panel-default">
								<div class="panel-heading">
									<ul>
										<li>
											<a class href="#collapseOne" data-toggle="collapse"><h5 class="panel-title"><i class="glyphicon glyphicon-flag"></i> Distance</h5></a>
										</li>
									</ul>
								</div>
								<div id="collapseOne" class="panel-collapse collapse in">
									<div class="panel-body filter-panel">
										<ul id="distance-filters" class="event-filters">
											<?php
												// Loop through distances
												for($i=1; $i<count($distanceNames); $i++)
													// If distance has races associated, show filter link and badge
													if($distanceCount[$i])
													{
														//if(isset($_GET["distance"]) && $_GET["distance"] == $distanceLinks[$i])
														//{
														//	echo '<li id="'.str_replace(" ", "_", $distanceNames[$i]).'-filter" class="inactive distance-filter">';
															// If filter is selected, make a link for removing the filter otherwise generate link to activate filter
															// if($regionVariable || $monthVariable || $surfaceVariable)
																// echo '<a>';
															// else
																// echo '<a href="'.BASE_URL.'/">';
														//}
														//else
															echo '<li id="'.str_replace(" ", "_", $distanceNames[$i]).'-filter" class="inactive distance-filter"><a>';

														// Filter name and badge
														echo $distanceNames[$i].'<span class="badge pull-right">'.$distanceCount[$i].'</span></a></li>';
													}
											?>
										</ul>
									</div>
								</div>
							</div>
							
							<!-- Region Filters ################################################-->							
							<div class="panel panel-default">
								<div class="panel-heading">
									<ul>
										<li>
											<a class<?= (!isset($_GET["region"])) ? '="collapsed"' : '' ?> href="#collapseTwo" data-toggle="collapse"><h5 class="panel-title"><i class="glyphicon glyphicon-map-marker"></i> Region</h5></a>
										</li>
									</ul>
								</div>
								<div id="collapseTwo" class="panel-collapse collapse<?= (isset($_GET["region"])) ? ' in' : '' ?>">
									<div class="panel-body filter-panel">
										<ul id="region-filters" class="event-filters">
											<?php  
												for($i=1; $i<count($regionNames); $i++)
													if($regionCount[$i])
													{
														//if(isset($_GET["region"]) && $_GET["region"] == $regionLinks[$i])
														//{
														//	echo '<li class="active">';
														//	if($monthVariable || $distanceVariable || $surfaceVariable)
														//		//echo '<a href="?'.$monthVariable.$dAnd.$distanceVariable.$sAnd.$surfaceVariable.'">';
														//		echo '<a>';
														//	else
														//		echo '<a href="'.BASE_URL.'/">';
														//}
														//else
															//echo '<li><a href="?'.$monthVariable.$dAnd.$distanceVariable.$sAnd.$surfaceVariable.$and.'region='.$regionLinks[$i].'">';
															echo '<li id="'.str_replace(" ", "_", $regionNames[$i]).'-filter" class="inactive region-filter"><a>';

														echo $regionNames[$i].'<span class="badge pull-right">'.$regionCount[$i].'</span></a></li>';
													}
											?>
										</ul>
									</div>
								</div>
							</div>

							<!-- Month Filters #######################################################-->
							<div class="panel panel-default">
								<div class="panel-heading">
									<ul>
										<li>
											<a class<?= (!isset($_GET["month"])) ? '="collapsed"' : '' ?> href="#collapseThree" data-toggle="collapse"><h5 class="panel-title"><i class="glyphicon glyphicon-calendar"></i> Month</h5></a>
										</li>
									</ul>
								</div>
								<div id="collapseThree" class="panel-collapse collapse<?= (isset($_GET["month"])) ? ' in' : '' ?>">
									<div class="panel-body filter-panel">
										<ul class="event-filters">
											<?php
												$divided = False;

												for($i=0; $i<12; $i++)
												{
													$month = date('F', strtotime("first day of +".$i." month"));
													$year = date('Y', strtotime("first day of +".$i." month"));

													if($year == date('Y')+1 && !$divided)
													{
														echo '<li role="presentation" class="dropdown-header">'.(date('Y')+1).'</li>';
														$divided = True;
													}
													if($monthCount[$i])
													{
														if(isset($_GET["month"]) && $_GET["month"] == $month)
														{
															echo '<li class="active">';
															if($regionVariable || $distanceVariable || $surfaceVariable)
																echo '<a href="?'.$regionVariable.$dAnd.$distanceVariable.$sAnd.$surfaceVariable.'">';
															else
																echo '<a href="'.BASE_URL.'/">';
														}
														else
															echo '<li><a href="?'.$regionVariable.$dAnd.$distanceVariable.$sAnd.$surfaceVariable.$and.'month='.$month.'">';

														echo $month.'<span class="badge pull-right">'.$monthCount[$i].'</span></a></li>';
													}
												}
											?>
										</ul>
									</div>
								</div>
							</div>

							<!-- Surface Filters ############################################-->
							<div class="panel panel-default">
								<div class="panel-heading">
									<ul>
										<li>
											<a class<?= (!isset($_GET["surface"])) ? '="collapsed"' : '' ?> href="#collapseFour" data-toggle="collapse"><h5 class="panel-title"><i class="glyphicon glyphicon-road"></i> Surface</h5></a>
										</li>
									</ul>
								</div>
								<div id="collapseFour" class="panel-collapse collapse<?= (isset($_GET["surface"])) ? ' in' : '' ?>">
									<div class="panel-body filter-panel">
										<ul class="event-filters">
											<?php
												for($i=1; $i<count($surfaceNames); $i++)
													if($surfaceCount[$i])
													{
														if(isset($_GET["surface"]) && $_GET["surface"] == $surfaceNames[$i])
														{
															echo '<li class="active">';
															if($regionVariable || $monthVariable || $distanceVariable)
																echo '<a href="?'.$regionVariable.$mAnd.$monthVariable.$dAnd.$distanceVariable.'">';
															else
																echo '<a href="'.BASE_URL.'/">';
														}
														else
															echo '<li><a href="?'.$regionVariable.$mAnd.$monthVariable.$dAnd.$distanceVariable.$and.'surface='.$surfaceNames[$i].'">';

														echo $surfaceNames[$i].'<span class="badge pull-right">'.$surfaceCount[$i].'</span></a></li>';
													}
											?>
										</ul>
									</div>
								</div>
							</div>
						</div>
						<!-- End Filter Column -->

						<!-- Race List Column -->
						<div class="col-md-9 event-column">
							<ul id="race-list" class="event_list">
								<?php
									while($row = $limitedResult->fetch())
										if($row->name && $row->distance)
											echo $row->race_entry;
								?>
							</ul>
							<?= $pageStatus ?>
						</div>
						<!-- End Race List Column -->

					</div>
				</div>
			</div>

			<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 pull-right">

				<!--###################################################################################################-->
				<!-- Nearby Races Panel ######################################################################################-->
				<?php
					$nearbyQuery = sprintf("SELECT * FROM atr_races WHERE date > '%s' ORDER BY date",
											$queryDate);
					$nearbyResult = $connection->query($nearbyQuery);

					($userPC) ? $PC = $userPC : $PC = 'Enter Postcode';
					
					if($nearbyResult)
					{
				?>
				<div class="panel">
					<div class="heading nearby-heading">
						<div class="page-header map-header">
							<a href="#postcode-accordion" data-toggle="collapse">
								<h4>Nearby Races <small><span id="userPC" class="status"><?= $PC ?></span></small></h4>
							</a>

							<div id="postcode-accordion" class="panel-collapse collapse">
								<form id="pc-form" class="form-inline">
									<div class="input-group">
										<input id="postcode" class="form-control" type="text" placeholder="<?= $PC ?>">
										<div class="input-group-btn">
											<button class="btn btn-success" name="postcode-form" type="submit" data-target="#postcode-accordion" data-toggle="collapse">
												<i class="glyphicon glyphicon-ok"></i>
											</button>
										</div>
									</div>
								</form>
								<br>
							</div>
						</div>
					</div>

					<span id="pc-err"></span>
					<div id="map"></div>

				</div>
				<?php } ?>
				<!-- End Nearby Races Panel -->

			</div>
		</div>
	</div>
</div>

<!-- Race Finder JavaScript -->
<script type="text/javascript">
	$(document.body).on('click', '.form-dropdown li', function(event){
		var $target = $(event.currentTarget);

		$target.closest('.btn-group-horizontal')
			.find('[data-bind="label"]').text($target.text())
			.end()
			.children('.dropdown-toggle').dropdown('toggle');

		$("#surface").val($('#surface_value').text());

		if($('#surface_value').text() == "XC (Cross Country)"){$("#surface").val("XC");}

		$target.closest('.input-group-btn')
			.find('[data-bind="label"]').text($target.text())
			.end()
			.children('.dropdown-toggle').dropdown('toggle');

		if($('#units_value').text() == "Marathon"){
			$("#distance").val("26.2");
			$("#units").val(" miles");
			$("#units_value").text("Miles");
		}else if($('#units_value').text() == "Half Marathon"){
			$("#distance").val("13.1");
			$("#units").val(" miles");
			$("#units_value").text("Miles");
		}else if($('#units_value').text() == "10K"){
			$("#distance").val("10");
			$("#units").val("km");
			$("#units_value").text("Kilometres");
		}else if($('#units_value').text() == "5K"){
			$("#distance").val("5");
			$("#units").val("km");
			$("#units_value").text("Kilometres");
		}else if($('#units_value').text() == "Miles"){$("#units").val(" miles");
		}else if($('#units_value').text() == "Kilometres"){$("#units").val("km");}

		return false;
	});

	$('#start_date').datepicker({
		format: 'dd-mm-yyyy',
		startDate: '<?= $date ?>',
		autoclose: 'true'
	});
	$('#end_date').datepicker({
		format: 'dd-mm-yyyy',
		startDate: '+1d',
		autoclose: 'true'
	});

	// Race Finder Panel - collapse status
	if($(window).width() >= 992){$('.race_finder').addClass('in');}
	$(window).resize(function(){
		if($(window).width() >= 992){$('.race_finder').addClass('in');}
		if($(window).width() < 992){$('.race_finder').removeClass('in');}
	});
</script>
<!-- End Race Finder JavaScript -->