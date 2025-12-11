<section class="synopsis">
    <h3>Used traits</h3>
    <div class="code">
        <com:Repeater id="List" onItemDataBind="List_DataBind">
            <prop:headerTemplate>
                <ul>
            </prop:headerTemplate>
            <prop:itemTemplate>
                    <li>
                        <com:HyperLink id="TraitName" html.class="type" />
                        <com:.classes.Comment id="Comment" />
                    </li>
            </prop:itemTemplate>
            <prop:footerTemplate>
                </ul>
            </prop:footerTemplate>
        </com:Repeater>
    </div>
</section>
