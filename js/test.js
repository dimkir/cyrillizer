var xhr = false;
var button1;
function test1(syllable_es, element_ru, button_el)
{
    button1 = button_el;
    var syllable_ru = element_ru.value;
    //alert('[' + syllable_es + '] to [' + syllable_ru + ']');
    
    button_el.disabled = true;
    button_el.value = "Saving...";

    
    
    if ( window.XMLHttpRequest )
    {
        xhr = new XMLHttpRequest();
    }
    else
    {
        if ( window.ActiveXObject )
            {
                try
                {
                  xhr = new ActiveXObject("Microsoft.XMLHTTP");   
                }
                catch ( e)
                {
                }
            }
    }
    
    

    if ( !xhr )
        {
            alert('Cant create XHR Cannot find nor window.XMLHttpRequest, neither ActiveX object');
            return;
        }
        
    xhr.onreadystatechange = showState;
    var urlBase = 'rest_save.php?es=' + syllable_es + '&ru=' + syllable_ru;
    xhr.open("GET", urlBase, true);
    xhr.send(null);

    
}


function showState()
{
    if ( xhr.readyState == 4)
    {
        button1.disabled = false;
        if ( xhr.status == 200)
            {
                button1.value = 'Completed succesfully: [' + xhr.responseText + ']';
            }
            else
                {
                    button1.value  = 'Failed .. (status:' + xhr.status + ')';
                }
        
    }
    else
       {
           button1.value = 'State changed to ' + xhr.readyState;
           
       }
        
        
    
}
