<?php
/**
 * 超级时光鸡
 * @author 即刻学术 www.ijkxs.com
 * @package custom
 */

require_once 'DyUtils.php';
//error_reporting(0);
$options = Helper::options();
/**
 * @param $_var_0 text
 * @param $_var_1 root url
 * @return string
 */
function typeLocationContent($_var_0, $_var_1)
{
	$_var_2 = mb_split('#', $_var_0);
	$_var_3 = $_var_2[2];
	$_var_4 = $_var_2[3];
	$_var_5 = DyUtils::uploadPic($_var_1, uniqid(), $_var_4, 'web', '.jpg');
	$_var_0 = 'P·' . $_var_3 . '<img src="' . $_var_5 . '"/>';
	return $_var_0;
}

/**
 * @param $_var_6 text imgurl
 * @param $_var_7 rootUrl
 * @return string
 */
function typeImageContent($_var_6, $_var_7)
{
	$_var_8 = DyUtils::uploadPic($_var_7, uniqid(), $_var_6, 'web', '.jpg');
	$_var_6 = '<img src="' . $_var_8 . '"/>';
	return $_var_6;
}
function typeTextContent($_var_9, $_var_10 = true)
{
	if ($_var_10) {
		$_var_9 = $_var_9 . '

';
	}
	return $_var_9;
}
function typeLinkContent($_var_11)
{
	$_var_12 = mb_split('#', $_var_11);
	$_var_13 = $_var_12[0];
	$_var_14 = $_var_12[1];
	$_var_15 = $_var_12[2];
	$_var_15 = str_replace('', '\\/', $_var_15);
	$_var_11 = '[post title="' . $_var_13 . '" intro="' . $_var_14 . '" url="' . $_var_15 . '" /]';
	return $_var_11;
}
function parseMixPostContent($_var_16, $_var_17)
{
	$_var_18 = json_decode($_var_16, true);
	$_var_18 = $_var_18['results'];
	$_var_16 = '';
	$_var_19 = false;
	$_var_20 = '[album]';
	foreach ($_var_18 as $_var_21) {
		if ($_var_21['type'] == 'image') {
			$_var_19 = true;
			$_var_16 .= typeImageContent($_var_21['content'], $_var_17->rootUrl);
		} elseif ($_var_21['type'] == 'text') {
			$_var_16 .= typeTextContent($_var_21['content'], true);
		} elseif ($_var_21['type'] == 'location') {
			$_var_16 .= typeLocationContent($_var_21['content'], $_var_17->rootUrl);
		} else {
			if ($_var_21['type'] == 'link') {
				$_var_16 = typeLinkContent($_var_21['content']);
			}
		}
	}
	return $_var_16;
}
function parseMixContent($_var_22, $_var_23)
{
	$_var_24 = json_decode($_var_22, true);
	$_var_24 = $_var_24['results'];
	$_var_22 = '';
	$_var_25 = false;
	$_var_26 = '[album]';
	foreach ($_var_24 as $_var_27) {
		if ($_var_27['type'] == 'image') {
			$_var_25 = true;
			$_var_26 .= typeImageContent($_var_27['content'], $_var_23->rootUrl);
		} elseif ($_var_27['type'] == 'text') {
			$_var_22 .= typeTextContent($_var_27['content'], true);
		} elseif ($_var_27['type'] == 'location') {
			$_var_22 .= typeLocationContent($_var_27['content'], $_var_23->rootUrl);
		} else {
			if ($_var_27['type'] == 'link') {
				$_var_22 = typeLinkContent($_var_27['content']);
			}
		}
	}
	if ($_var_25) {
		$_var_26 .= '[/album]';
		$_var_22 .= typeTextContent($_var_26, false);
	}
	return $_var_22;
}

// 获取时光机页面的 time_code
function getTimeCode($cid){
    $db = Typecho_Db::get();
    $value = $db->fetchObject($db->select('str_value')->from('table.fields')->where('cid = ? and name = ?',$cid,'time_code'))->str_value;
    return md5($value);

}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (@$_POST['action'] == 'send_talk') {
		if (!empty($_POST['content']) && !empty($_POST['time_code']) && !empty($_POST['cid']) && !empty($_POST['token'])) {
			$cid = $_POST['cid'];
			$thisText = $_POST['content'];
			$time_code = $_POST['time_code'];
			$token = $_POST['token'];
			$msg_type = $_POST['msg_type'];
			//$options = Helper::options();
            $user_time_code = getTimeCode($cid);

            if (md5($time_code) == $user_time_code) {
				if ($msg_type == 'mixed_post') {
					$thisText = '<!--markdown-->' . parseMixPostContent($thisText, $options);
					$mid = $_POST['mid'];
					$db = Typecho_Db::get();
					$getAdminSql = $db->select()->from('table.users')->limit(1);
					$user = $db->fetchRow($getAdminSql);
					$time = date('Y 年 m 月 d 日');
					$timeSlug = date('Y-n-j-H:i:s', time());
					$insert = $db->insert('table.contents')->rows(array('title' => $time, 'slug' => $timeSlug, 'created' => time(), 'modified' => time(), 'text' => $thisText, 'authorId' => $user['uid']));
					$insertId = $db->query($insert);
					$insert = $db->insert('table.relationships')->rows(array('cid' => $insertId, 'mid' => $mid));
					$insertId = $db->query($insert);
					$row = $db->fetchRow($db->select('count')->from('table.metas')->where('mid = ?', $mid));
					$db->query($db->update('table.metas')->rows(array('count' => (int) $row['count'] + 1))->where('mid = ?', $mid));
					echo '1';
				} else {
					if ($msg_type == 'image') {
						$thisText = typeImageContent($thisText, $options->rootUrl);
					} else {
						if ($msg_type == 'location') {
							$thisText = typeLocationContent($thisText, $options->rootUrl);
						} else {
							if ($msg_type == 'mixed_talk') {
								$thisText = parseMixContent($thisText, $options);
							} else {
								if ($msg_type == 'text') {
									$thisText = typeTextContent($thisText, false);
								} else {
									if ($msg_type == 'link') {
										$thisText = typeLinkContent($thisText);
									}
								}
							}
						}
					}
					$db = Typecho_Db::get();
					$getAdminSql = $db->select()->from('table.users')->limit(1);
					$user = $db->fetchRow($getAdminSql);
					$insert = $db->insert('table.comments')->rows(array('cid' => $cid, 'created' => time(), 'author' => $user['screenName'], 'authorId' => $user['uid'], 'ownerId' => $user['uid'], 'text' => $thisText, 'url' => $user['url'], 'mail' => $user['mail'], 'agent' => $token));
					$insertId = $db->query($insert);
					$row = $db->fetchRow($db->select('commentsNum')->from('table.contents')->where('cid = ?', $cid));
					$db->query($db->update('table.contents')->rows(array('commentsNum' => (int) $row['commentsNum'] + 1))->where('cid = ?', $cid));
					echo '1';
				}
			} else {
				echo '-3';
			}
		} else {
			echo '-2';
		}
		die;
	} elseif (@$_POST['action'] == 'send_post') {
		if (!empty($_POST['content']) && !empty($_POST['time_code']) && !empty($_POST['cid']) && !empty($_POST['token'])) {
		}
	} else {
		if (@$_POST['action'] == 'upload_img') {
			$returnData = array();
		//	$options = Helper::options();
			$flag = false;
			if ($this->user->hasLogin()) {
				$flag = true;
			} elseif ($_POST['time_code'] == md5($options->time_code) && trim($options->time_code) !== '') {
				$flag = true;
			} else {
				$flag = false;
			}
			if ($flag) {
				$data = $_POST['file'];
				$suffix = @$_POST['type'];
				if ($suffix == '') {
					$suffix = '.jpg';
				}
				$prefix = substr($data, 0, 4);
				if ($prefix == 'data') {
					$base64_string = explode(',', $data);
					$data = base64_decode($base64_string[1]);
					$returnData['status'] = '1';
					$returnData['data'] = DyUtils::uploadPic($options->rootUrl, uniqid(), $data, 'local', $suffix);
				} else {
					if ($prefix == 'http') {
						$returnData['status'] = '1';
						$returnData['data'] = DyUtils::uploadPic($options->rootUrl, uniqid(), $data, 'web', '.jpg');
					} else {
						$returnData['status'] = '-1';
					}
				}
			} else {
				$returnData['status'] = '-3';
			}
			echo json_encode($returnData);
			die;
		} 
	}
} else {
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		if (@$_GET['action'] == 'ajax_avatar_get') {
			$email = strtolower($_GET['email']);
			echo DyUtils::getAvator($email, 65);
			die;
		} elseif (@$_GET['action'] == 'send_talk') {
			echo '非法get请求';
			die;
		} else {
			if (@$_GET['action'] == 'star_talk') {
				if (!empty($_GET['coid'])) {
					$coid = $_GET['coid'];
					$db = Typecho_Db::get();
					$stars = Typecho_Cookie::get('extend_say_stars');
					if (empty($stars)) {
						$stars = array();
					} else {
						$stars = explode(',', $stars);
					}
					$row = $db->fetchRow($db->select('stars')->from('table.comments')->where('coid = ?', $coid));
					if (!in_array($coid, $stars)) {
						$db->query($db->update('table.comments')->rows(array('stars' => (int) $row['stars'] + 1))->where('coid = ?', $coid));
						array_push($stars, $coid);
						$stars = implode(',', $stars);
						Typecho_Cookie::set('extend_say_stars', $stars);
						echo 1;
					} else {
						echo 2;
					}
				} else {
					echo -1;
				}
				die;
			} else {
				if (@$_GET['action'] == 'get_search_cache') {
					header('Content-type:text/json');
					$filePath = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . DIRECTORY_SEPARATOR . 'Handsome' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'search.json';
					$file = file_get_contents($filePath);
					if ($file !== false) {
						echo $file;
					} else {
						echo '{}';
					}
					die;
				} else {
					if (@$_GET['action'] == 'open_world') {
						if (!empty($_GET['password'])) {
							$password = $_GET['password'];
							$md5 = $_GET['md5'];
							$type = $_GET['type'];
						//	$options = Helper::options();
							if (DyUtils::encodeData($password) == $md5) {
								echo 1;
								if ($type == 'index') {
									Typecho_Cookie::set('open_new_world', DyUtils::encodeData($password));
								} elseif ($type == 'category') {
									$category = $_GET['category'];
									Typecho_Cookie::set('category_' . $category, DyUtils::encodeData($password));
								}
							} else {
								echo -1;
							}
						} else {
							echo -2;
						}
						die;
					} else {
						if (@$_GET['action'] == 'back_up' || @$_GET['action'] == 'un_back_up' || @$_GET['action'] == 'recover_back_up') {
							$action = $_GET['action'];
							$db = Typecho_Db::get();
							$themeName = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'theme'));
							$handsomeThemeName = 'theme:' . $themeName['value'];
							$handsomeThemeBackupName = 'theme:HandsomePro-X-Backup';
							if ($action == 'back_up') {
								$handsomeInfo = $db->fetchRow($db->select()->from('table.options')->where('name = ?', $handsomeThemeName));
								$handsomeValue = $handsomeInfo['value'];
								if ($db->fetchRow($db->select()->from('table.options')->where('name = ?', $handsomeThemeBackupName))) {
									$update = $db->update('table.options')->rows(array('value' => $handsomeValue))->where('name = ?', $handsomeThemeBackupName);
									$updateRows = $db->query($update);
									echo 1;
								} else {
									$insert = $db->insert('table.options')->rows(array('name' => $handsomeThemeBackupName, 'user' => '0', 'value' => $handsomeValue));
									$db->query($insert);
									echo 2;
								}
							} else {
								if ($action == 'un_back_up') {
									$db = Typecho_Db::get();
									if ($db->fetchRow($db->select()->from('table.options')->where('name = ?', $handsomeThemeBackupName))) {
										$delete = $db->delete('table.options')->where('name = ?', $handsomeThemeBackupName);
										$deletedRows = $db->query($delete);
										echo 1;
									} else {
										echo -1;
									}
								} else {
									if ($action == 'recover_back_up') {
										$db = Typecho_Db::get();
										if ($db->fetchRow($db->select()->from('table.options')->where('name = ?', $handsomeThemeBackupName))) {
											$themeInfo = $db->fetchRow($db->select()->from('table.options')->where('name = ?', $handsomeThemeBackupName));
											$themeValue = $themeInfo['value'];
											$update = $db->update('table.options')->rows(array('value' => $themeValue))->where('name = ?', $handsomeThemeName);
											$updateRows = $db->query($update);
											echo 1;
										} else {
											echo -1;
										}
									}
								}
							}
							die;
						} else {
							if (@$_GET['action'] == 'ajax_search') {
								header('Content-type:text/json');
								$thisText = @$_GET['content'];
								$object = [];
								$filePath = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . DIRECTORY_SEPARATOR . 'Handsome' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'search.json';
								$file = file_get_contents($filePath);
								$cache = json_decode($file, true);
								$html = '';
								$resultLength = 0;
								if (trim($thisText) !== '') {
									$searchArray = mb_split(' ', $thisText);
									$searchArray[] = $thisText;
									$searchResultArray = [];
									foreach ($searchArray as $thisText) {
										if (trim($thisText) != '') {
											foreach ($cache as $item) {
												$content_ok = mb_stripos($item['content'], $thisText);
												if ($content_ok !== false) {
													$contentMatch = mb_substr($item['content'], max(0, $content_ok - 10), min(20, mb_strlen($item['content'], 'utf8') - $content_ok));
													$contentMatch = str_ireplace($thisText, '<mark class=\'text_match\'>' . $thisText . '</mark>', $contentMatch);
													$searchResultArray[] = array('path' => $item['path'], 'title' => $item['title'], 'content' => $contentMatch);
													$resultLength++;
												} else {
													$title_ok = mb_stripos($item['title'], $thisText);
													if ($title_ok !== false) {
														$contentMatch = mb_substr($item['content'], 0, min(30, mb_strlen($item['content']) - $title_ok));
														$contentMatch = str_ireplace($thisText, '<mark class=\'text_match\'>' . $thisText . '</mark>', $contentMatch);
														$searchResultArray[] = array('path' => $item['path'], 'title' => $item['title'], 'content' => $contentMatch);
														$resultLength++;
													} else {
														continue;
													}
												}
											}
										}
									}
									$searchResultArray = DyUtils::array_unset_tt($searchResultArray, 'path');
									if (count($searchResultArray) === 0) {
										$html = '<li><a href="#">无相关搜索结果🔍</a></li>';
									} else {
										foreach ($searchResultArray as $item) {
											$html .= '<li><a href="' . $item['path'] . '">' . $item['title'] . '<p class="text-muted">' . $item['content'] . '</p></a></li>';
										}
									}
								}
								$object['results'] = $html;
								echo json_encode($object);
								die;
							} else {
						//		$options = Helper::options();
								$password = Typecho_Cookie::get('open_new_world');
								$cookie = false;
								if (!empty($password) && $password == DyUtils::encodeData($options->open_new_world)) {
									$cookie = true;
								}
								if (!$cookie && trim($options->open_new_world) != '' && strpos($_SERVER['SCRIPT_NAME'], __TYPECHO_ADMIN_DIR__) === false) {
									$data = array();
									$data['title'] = $this->options->title;
									$data['md5'] = DyUtils::encodeData($options->open_new_world);
									$data['type'] = 'index';
									$data['category'] = '';
									$_GET['data'] = $data;
									require_once 'Lock.php';
									die;
								} 
							}
						}
					}
				}
			}
		}
	}
}