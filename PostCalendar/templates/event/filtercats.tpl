{* $Id: postcalendar_event_filtercats.htm 596 2010-06-05 01:14:46Z craigh $ *}
{gt text="All These Categories" assign="allText"}
{nocache}
{foreach from=$catregistry key=property item=category}
    {array_field_isset assign="selectedValue" array=$selectedcategories field=$property returnValue=1}
    {selector_LivePipeMultCats 
    editLink=0 
    category=$category 
    name="postcalendar_events[__CATEGORIES__][$property]" 
    field="id" 
    selectedValue=$selectedValue 
    defaultValue="0"
    all=1
    allText=$allText
    allValue=0}
{/foreach}
{/nocache}
