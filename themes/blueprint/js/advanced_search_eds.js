/*global addSearchString, deleteSearchGroupString, searchFieldLabel, searchFields, searchJoins, searchLabel, searchMatch*/

var nextGroupNumber = 0;
var groupSearches = [];

function jsEntityEncode(str)
{
    var new_str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    return new_str;
}



function reNumGroup(oldGroup, newNum)
{
    // Keep the old details for use
    var oldId  = $(oldGroup).attr("id");
    var oldNum = oldId.substring(5, oldId.length);

    // Which alternating row we're on
    var alt = newNum % 2;

    // Make sure the function was called correctly
    if (oldNum != newNum) {
        // Update the delete link with the new ID
        $("#delete_link_" + oldNum).attr("id", "delete_link_" + newNum);

        // Update the bool[] parameter number
        $(oldGroup).find("[name='bool" + oldNum + "[]']:first").attr("name", "bool" + newNum + "[]");

        // Update the add term link with the new ID
        $("#add_search_link_" + oldNum).attr("id", "add_search_link_" + newNum);

        // Now loop through and update all lookfor[] and type[] parameters
        $("#group"+ oldNum + "SearchHolder").find("[name='lookfor" + oldNum + "[]']").each(function() {
            $(this).attr("name", "lookfor" + newNum + "[]");
        });
        $("#group"+ oldNum + "SearchHolder").find("[name='type" + oldNum + "[]']").each(function() {
            $(this).attr("name", "type" + newNum + "[]");
        });

        // Update search holder ID
        $("#group"+ oldNum + "SearchHolder").attr("id", "group" + newNum + "SearchHolder");

        // Finally, re-number the group itself
        $(oldGroup).attr("id", "group" + newNum).attr("class", "group group" + alt);
    }
}

function reSortGroups()
{
    // Loop through all groups
    var groups = 0;
    $("#searchHolder > .group").each(function() {
        // If the number of this group doesn't
        //   match our running count
        if ($(this).attr("id") != "group"+groups) {
            // Re-number this group
            reNumGroup(this, groups);
        }
        groups++;
    });
    nextGroupNumber = groups;
    //always hide these for EDS API!
    $("#groupJoin").hide();
    $("#delete_link_0").hide();
    
}

function addGroup(firstTerm, firstField, join)
{
    if (firstTerm  == undefined) {firstTerm  = '';}
    if (firstField == undefined) {firstField = '';}
    if (join       == undefined) {join       = '';}

    var newGroup = '<div id="group' + nextGroupNumber + '" class="group group' + (nextGroupNumber % 2) + '">';

    newGroup += '<div id="group' + nextGroupNumber + 'SearchHolder" class="groupSearchHolder">';
    newGroup += '<div class="advRow">';
    if(0 == nextGroupNumber){
    	newGroup += '<div class="operator hide">';
    }else
    	newGroup += '<div class="operator">';
    	
    newGroup += '<select id="search_bool' + nextGroupNumber + '" name="bool' + nextGroupNumber + '[]">';
    for (var key in searchJoins) {
        newGroup += '<option value="' + key + '"';
        if (key == join) {
            newGroup += ' selected="selected"';
        }
        newGroup += '>' + searchJoins[key] + '</option>';
    };
    newGroup += '</select>';
    newGroup += '</div>';

    groupSearches[nextGroupNumber] = 0;
    
    // Label
    group = nextGroupNumber;    
    term = firstTerm;
    field = firstField;
    
    newGroup += '<div class="label"><label ';
    newGroup += 'class="hide"';
    newGroup += ' for="search_lookfor' + group + '_' + groupSearches[group] + '">' + searchLabel + ':</label>&nbsp;</div>';

    // Terms
    newGroup += '<div class="terms"><input type="text" id="search_lookfor' + group + '_' + groupSearches[group] + '" name="lookfor' + group + '[]" size="50" value="' + jsEntityEncode(term) + '"/></div>';

    // Field
    newGroup += '<div class="field"><label for="search_type' + group + '_' + groupSearches[group] + '">' + searchFieldLabel + '</label> ';
    newGroup += '<select id="search_type' + group + '_' + groupSearches[group] + '" name="type' + group + '[]">';
    for (var key in searchFields) {
    	newGroup += '<option value="' + key + '"';
        if (key == field) {
        	newGroup += ' selected="selected"';
        }
        newGroup += ">" + searchFields[key] + "</option>";
    }
    newGroup += '</select>';
    newGroup += '</div>';

    // Handle floating nonsense
    newGroup += '<span class="clearer"></span>';
    
    newGroup += '</div>'; 
    
    newGroup += '</div>';
    newGroup += '</div>';

    // Add the new group into the page
    $("#searchHolder").append(newGroup);

    // Keep the page in order
    reSortGroups();

    // Pass back the number of this group
    return nextGroupNumber - 1;
}

function deleteGroup(group)
{
    // Find the group and remove it
    $("#group" + group).remove();
    // And keep the page in order
    reSortGroups();
    // If the last group was removed, add an empty group
    if (nextGroupNumber == 0) {
        addGroup();
    }
}

// Fired by onclick event
function deleteGroupJS(group)
{
    var groupNum = group.id.replace("delete_link_", "");
    deleteGroup(groupNum);
    return false;
}

// Fired by onclick event
function addSearchJS(group)
{
    var groupNum = group.id.replace("add_search_link_", "");
    addSearch(groupNum);
    return false;
}