
<div>
    <a href="maillog.php?tab=dovecot_logs"><button class="btn {if $tab=='dovecot_logs'}active{/if}" style="margin-bottom: 25px;"><span class="glyphicon glyphicon glyphicon-align-left"> </span> IMAP/POP3</button></a>
    <a href="maillog.php?tab=postfix_logs"><button class="btn {if $tab=='postfix_logs'}active{/if}" style="margin-bottom: 25px;"><span class="glyphicon glyphicon glyphicon-align-right"></span> SMTP</button></a>
    <form name="search" method="post" action="maillog.php?tab={$tab}">
        <input type='hidden' name="cursor" id="fCursor" value="" />
        <input type='hidden' name="prev" id="fPrev" value="" />
        <table class="table table-bordered table-striped">
        <thead id="table_header">
        <tr>
            <th>
                <label for="postfix_month">Date</label>
                <div class="input-group date" id="datetimepicker">
                    <input type='text' name="date" id="fActiveFromForm" value="{$date}" class="form-control" />
                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                </div>
            </th>
            <th>
                <label for="postfix_status">Message</label>
                <div class="input-group">
                    <input class="form-control" type="text" name="search" value="{$search}">
                    <span class="input-group-addon"><span class="glyphicon glyphicon-search"></span> <input class="button" type="submit" name="go" value="Search" /></span>
                </div>
            </th>
        </tr>
        </thead>
        <tbody id="content_logs">
        {foreach from=$tLog item=item key=cursor}
            <tr>
                <td>{$item.time}</td>
                <td style="{if $item.level<6}color: orange;{elseif $item.level<4}color: red;{/if}">{$item.message}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    </form>

    <div class="row" id="pages">
        <div class="col-md-4">
        </div>

        <div class="col-md-4 col-md-offset-4">
            <div class="pull-right" style="margin: 10px;">

                <div class="btn-group mr-2" role="group">
                    <button id="Prev" type="button" onclick="go2page('{$first_cursor}', '1')" class="btn btn-secondary">&larr; Prev</button>
                    <button id="Next" type="button" onclick="go2page('{$last_cursor}', '0')" class="btn btn-secondary">Next &rarr;</button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function go2page(cursor, prev){
        $('#fCursor').val(cursor);
        $('#fPrev').val(prev);
        $('form[name=search]').submit();
    }
    {literal}
    $(function () {
        // See: https://momentjs.com/docs/#/displaying/format/ for format spec.
        // See: https://getdatepicker.com/4/Options/ for docs
        $('#datetimepicker').datetimepicker({
            ignoreReadonly: true,
            //     locale: locale,
            showTodayButton: true,
            showClear: true,
            showClose: true,
            allowInputToggle: true,
            maxDate: moment(),
            format: 'YYYY-MM-DD HH:mm:ss',  // should use 'L' but it's crappy mm/dd/YYYY format for me in the U.K.
            date: $('#fActiveFromForm').val(),

        });
    });
    {/literal}
    {if $hidePrev}
        $('#Prev').hide();
    {/if}
    {if $hideNext}
        $('#Next').hide();
    {/if}
</script>
