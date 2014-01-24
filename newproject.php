<?php
require_once 'core/Init.php';

$user = new User();
if($user->isLoggedIn()) {
    if(Input::exists()) {
        if($_FILES['uploadedfile']['size'] != 0) {
            if($_FILES['uploadedfile']['type'] == 'text/plain' || $_FILES['uploadedfile']['type'] == 'text/xml') {
                $target_path = "queue/";
                $target_path = $target_path . basename( $_FILES['uploadedfile']['name']);

                if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
                    $parser = new Parser();
                    $parser->nmapParse('queue/'.$_FILES['uploadedfile']['name']);

                    $hosts = $parser->nmap();
                    foreach($hosts as $ips => $ip) {
                        foreach($ip as $index => $ports) {
                            foreach($ports as $port => $services) {
                                $hosts = DB::getInstance()->insertAssoc('hosts', array(
                                    'host' => $ips,
                                    'port' => $port,
                                    'protocol' => $services['Protocol'],
                                    'state' => $services['State'],
                                    'reason' => $services['Reason'],
                                    'name' => $services['Name'],
                                    'product' => $services['Product'],
                                    'version' => $services['Version'],
                                    'project' => htmlentities(Input::get('projectname')),
                                    'startedby' => $user->data()->username
                                ));
                            }
                        }
                    }
                    Redirect::to('index.php');
                } else{
                    echo "There was an error uploading the file, please try again!";
                    Redirect::to('index.php');
                }
            } else {
                echo "Invalid File Type";
                Redirect::to('index.php');
            }
        } else {
            $hosts = DB::getInstance()->insertAssoc('hosts', array(
                'project' => htmlentities(Input::get('projectname')),
                'startedby' => $user->data()->username
            ));
            Redirect::to('index.php');
        }
    }
    $page = new Page;
    $page->setTitle('New Project');
    $page->startBody();
    ?>


    <div class="panel panel-primary">

        <div class="panel-heading">
            <div class="panel-title">New Project</div>

            <div class="panel-options">
                <a href="#" data-rel="collapse"><i class="entypo-down-open"></i></a>
                <a href="#" data-rel="reload"><i class="entypo-arrows-ccw"></i></a>
            </div>
        </div>

        <div class="panel-body">
            <form enctype="multipart/form-data" action="" method="POST">
                <div class="form-group">
                    <label class="control-label">Project Name</label>
                    <input type="text" class="form-control" name="projectname" id="projectname" data-validate="required" data-message-required="Project Name is Required" placeholder="Project Name" />
                    <br><br><br>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">File Select (XML or TXT Only)</label>
                        <div class="col-sm-5">
                            <div class="fileinput fileinput-new" data-provides="fileinput">
                                <div class="input-group">
                                    <div class="form-control uneditable-input" data-trigger="fileinput">
                                        <i class="glyphicon glyphicon-file fileinput-exists"></i>
                                        <span class="fileinput-filename"></span>
                                    </div>
									<span class="input-group-addon btn btn-default btn-file">
										<span class="fileinput-new">Select file</span>
										<span class="fileinput-exists">Change</span>
										<input type="file" name="uploadedfile">
									</span>
                                    <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="MAX_FILE_SIZE" value="99999999999999999">
                </div>
                <div class="form-group">
                    <br><br><br>
                    <button type="submit" class="btn btn-success">Add Project</button>
                    <button type="reset" class="btn">Reset</button>
                </div>

            </form>

        </div>
    </div>
    <?php
    $page->endBody();
    echo $page->render('includes/template.php');

} else {
    Redirect::to('login.php');
}