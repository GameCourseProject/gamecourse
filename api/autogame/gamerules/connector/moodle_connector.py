#!/usr/bin/env python3

from gamerules.connector.gamecourse_connector import *

try:
    from gamerules.connector.db_connector import moodle_db
except ImportError:
    raise Exception('Moodle is not configured properly — database password is missing.')


### ------------------------------------------------------ ###
###	------------------ PUBLIC functions ------------------ ###
### -- (accessible in rules through imported functions) -- ###
### ------------------------------------------------------ ###

### Getting logs

def get_consecutive_peergrading_logs(target):
    """
    Gets consecutive peergrading logs done by target.
    """

    if not module_enabled("Moodle"):
        raise Exception("Can't get consecutive peergrading logs: Moodle is not enabled.")

    # Get target username
    # NOTE: target GC username = target Moodle username
    query = "SELECT username FROM auth WHERE user = %s;" % target
    username = (gc_db.data_broker.get(gc_db, target, query, "user")[0][0]).decode()

    # Get Moodle info
    query = "SELECT tablesPrefix, moodleCourse FROM moodle_config WHERE course = %s;" % config.COURSE
    mdl_prefix, mdl_course = gc_db.data_broker.get(gc_db, config.COURSE, query)[0]
    mdl_prefix = mdl_prefix.decode()

    # Get peergrades assigned to target
    query = "SELECT f.name as forumName, fd.id as discussionId, fp.subject, fp.id as postId, u.username, pa.expired, pa.peergraded " \
            "FROM " + mdl_prefix + "peerforum_time_assigned pa JOIN " + mdl_prefix + "peerforum_posts fp on pa.itemid=fp.id " \
            "JOIN " + mdl_prefix + "peerforum_discussions fd on fd.id=fp.discussion " \
            "JOIN " + mdl_prefix + "peerforum f on f.id=fd.peerforum " \
            "JOIN " + mdl_prefix + "user u on fp.userid=u.id " \
            "JOIN " + mdl_prefix + "user ug on pa.userid=ug.id " \
            "JOIN " + mdl_prefix + "course c on f.course=c.id " \
            "WHERE f.course = %s AND ug.username = '%s'" \
            "ORDER BY pa.timeassigned;" % (mdl_course, username)
    peergrades = moodle_db.execute_query(query)

    # Get consecutive peergrading logs
    consecutive_logs = []
    last_peergrading = None

    for peergrade in peergrades:
        expired = bool(peergrade[5])
        peergrade_id = int(peergrade[6])
        peergraded = not expired and peergrade_id > 0

        if not peergraded:
            last_peergrading = None
            continue

        # Get GC user id of peergrade
        query = "SELECT user FROM auth WHERE username = '%s';" % peergrade[4].decode()
        user_id = int(gc_db.data_broker.get(gc_db, target, query, "user")[0][0])

        # Get peergrade info
        query = "SELECT peergrade as grade FROM " + mdl_prefix + "peerforum_peergrade WHERE id = %s;" % peergrade_id
        grade = int(moodle_db.execute_query(query)[0][0])

        # Get actual GC log
        forum_name = peergrade[0].decode()
        thread = peergrade[2].decode()
        logs = get_logs(user_id, "peergraded post", grade, target, None, None, forum_name + ", " + thread)
        if len(logs) > 1:
            discussion_id = str(peergrade[1])
            post_id = str(peergrade[3])
            logs = [log for log in logs if compare_with_wildcards(log[config.LOG_POST_COL], "%peerforum%?d=" + discussion_id + "#p" + post_id)]

        if len(logs) == 1:
            log = logs[0]
        else:
            last_peergrading = None
            continue

        if last_peergrading is not None:
            consecutive_logs[-1].append(log)
        else:
            consecutive_logs.append([log])
        last_peergrading = peergraded

    return consecutive_logs
