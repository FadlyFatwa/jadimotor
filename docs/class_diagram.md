<mxGraphModel dx="2575" dy="1405" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="827" pageHeight="1169" math="0" shadow="0">
  <root>
    <mxCell id="0" />
    <mxCell id="1" parent="0" />
    <mxCell id="B01" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«boundary»&lt;br&gt;&lt;b&gt;SawKriteriaIndex&lt;/b&gt;" vertex="1">
      <mxGeometry height="42" width="160" x="70" y="60" as="geometry" />
    </mxCell>
    <mxCell id="B02" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«boundary»&lt;br&gt;&lt;b&gt;SawKriteriaForm&lt;/b&gt;" vertex="1">
      <mxGeometry height="42" width="155" x="245" y="60" as="geometry" />
    </mxCell>
    <mxCell id="B03" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«boundary»&lt;br&gt;&lt;b&gt;SawHistorisIndex&lt;/b&gt;" vertex="1">
      <mxGeometry height="42" width="165" x="440" y="60" as="geometry" />
    </mxCell>
    <mxCell id="B04" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«boundary»&lt;br&gt;&lt;b&gt;SawHistorisForm&lt;/b&gt;" vertex="1">
      <mxGeometry height="42" width="155" x="625" y="60" as="geometry" />
    </mxCell>
    <mxCell id="B05" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«boundary»&lt;br&gt;&lt;b&gt;PemilihanSupplierIndex&lt;/b&gt;" vertex="1">
      <mxGeometry height="42" width="195" x="815" y="60" as="geometry" />
    </mxCell>
    <mxCell id="B06" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«boundary»&lt;br&gt;&lt;b&gt;PemilihanSupplierRingkasan&lt;/b&gt;" vertex="1">
      <mxGeometry height="42" width="215" x="1030" y="60" as="geometry" />
    </mxCell>
    <mxCell id="B07" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«boundary»&lt;br&gt;&lt;b&gt;PemilihanSupplierShow&lt;/b&gt;" vertex="1">
      <mxGeometry height="42" width="205" x="1320" y="60" as="geometry" />
    </mxCell>
    <mxCell id="C01" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«control»&lt;br&gt;&lt;b&gt;SawKriteriaController&lt;/b&gt;" vertex="1">
      <mxGeometry height="235" width="330" x="70" y="165" as="geometry" />
    </mxCell>
    <mxCell id="2" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ index() : View" vertex="1">
      <mxGeometry height="18" width="330" y="42" as="geometry" />
    </mxCell>
    <mxCell id="3" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ create() : View" vertex="1">
      <mxGeometry height="18" width="330" y="60" as="geometry" />
    </mxCell>
    <mxCell id="4" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ store(Request) : RedirectResponse" vertex="1">
      <mxGeometry height="18" width="330" y="78" as="geometry" />
    </mxCell>
    <mxCell id="5" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ edit(SawKriteria) : View" vertex="1">
      <mxGeometry height="18" width="330" y="96" as="geometry" />
    </mxCell>
    <mxCell id="6" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ update(Request, SawKriteria) : RedirectResponse" vertex="1">
      <mxGeometry height="18" width="330" y="114" as="geometry" />
    </mxCell>
    <mxCell id="7" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ destroy(SawKriteria) : RedirectResponse" vertex="1">
      <mxGeometry height="18" width="330" y="132" as="geometry" />
    </mxCell>
    <mxCell id="8" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- bobotTerlampaui(array, ?SawKriteria) : ?string" vertex="1">
      <mxGeometry height="18" width="330" y="150" as="geometry" />
    </mxCell>
    <mxCell id="9" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- validateData(Request, ?SawKriteria) : array" vertex="1">
      <mxGeometry height="18" width="330" y="168" as="geometry" />
    </mxCell>
    <mxCell id="200" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- denyViewOnly() : void" vertex="1">
      <mxGeometry height="18" width="330" y="186" as="geometry" />
    </mxCell>
    <mxCell id="201" parent="C01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- generateNextKode() : string" vertex="1">
      <mxGeometry height="18" width="330" y="204" as="geometry" />
    </mxCell>
    <mxCell id="C02" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«control»&lt;br&gt;&lt;b&gt;SawHistorisController&lt;/b&gt;" vertex="1">
      <mxGeometry height="265" width="360" x="430" y="160" as="geometry" />
    </mxCell>
    <mxCell id="10" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ index(Request) : View" vertex="1">
      <mxGeometry height="18" width="360" y="42" as="geometry" />
    </mxCell>
    <mxCell id="11" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ create() : View" vertex="1">
      <mxGeometry height="18" width="360" y="60" as="geometry" />
    </mxCell>
    <mxCell id="12" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ store(Request) : RedirectResponse" vertex="1">
      <mxGeometry height="18" width="360" y="78" as="geometry" />
    </mxCell>
    <mxCell id="13" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ edit(SawNilaiHistoris) : View" vertex="1">
      <mxGeometry height="18" width="360" y="96" as="geometry" />
    </mxCell>
    <mxCell id="14" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ update(Request, SawNilaiHistoris) : RedirectResponse" vertex="1">
      <mxGeometry height="18" width="360" y="114" as="geometry" />
    </mxCell>
    <mxCell id="15" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ destroy(SawNilaiHistoris) : RedirectResponse" vertex="1">
      <mxGeometry height="18" width="360" y="132" as="geometry" />
    </mxCell>
    <mxCell id="16" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- denyViewOnly() : void" vertex="1">
      <mxGeometry height="18" width="360" y="150" as="geometry" />
    </mxCell>
    <mxCell id="17" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- normalizeDecimalInputs(Request) : void" vertex="1">
      <mxGeometry height="18" width="360" y="168" as="geometry" />
    </mxCell>
    <mxCell id="18" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- kriteriaCustomAktif() : Collection" vertex="1">
      <mxGeometry height="18" width="360" y="186" as="geometry" />
    </mxCell>
    <mxCell id="19" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- kriteriaDinamisAktif() : Collection" vertex="1">
      <mxGeometry height="18" width="360" y="204" as="geometry" />
    </mxCell>
    <mxCell id="20" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- kriteriaDinamisValidationRules() : array" vertex="1">
      <mxGeometry height="18" width="360" y="222" as="geometry" />
    </mxCell>
    <mxCell id="21" parent="C02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- syncNilaiKriteria(SawNilaiHistoris, array) : void" vertex="1">
      <mxGeometry height="18" width="360" y="240" as="geometry" />
    </mxCell>
    <mxCell id="C03" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«control»&lt;br&gt;&lt;b&gt;SupplierRecommendationController&lt;/b&gt;" vertex="1">
      <mxGeometry height="123" width="360" x="850" y="165" as="geometry" />
    </mxCell>
    <mxCell id="22" parent="C03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ index() : View" vertex="1">
      <mxGeometry height="18" width="360" y="42" as="geometry" />
    </mxCell>
    <mxCell id="23" parent="C03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ ringkasan($id) : View" vertex="1">
      <mxGeometry height="18" width="360" y="60" as="geometry" />
    </mxCell>
    <mxCell id="24" parent="C03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ show($id) : View" vertex="1">
      <mxGeometry height="18" width="360" y="78" as="geometry" />
    </mxCell>
    <mxCell id="25" parent="C03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- statusPemilihanDariGroups(array) : string" vertex="1">
      <mxGeometry height="18" width="360" y="96" as="geometry" />
    </mxCell>
    <mxCell id="C04" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«control»&lt;br&gt;&lt;b&gt;SupplierConfirmationController&lt;/b&gt;" vertex="1">
      <mxGeometry height="105" width="345" x="1319" y="174" as="geometry" />
    </mxCell>
    <mxCell id="26" parent="C04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ saveSelection(Request, $id) : RedirectResponse" vertex="1">
      <mxGeometry height="18" width="345" y="42" as="geometry" />
    </mxCell>
    <mxCell id="27" parent="C04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- buildGroupsForNeedlist(Needlist) : array" vertex="1">
      <mxGeometry height="18" width="345" y="60" as="geometry" />
    </mxCell>
    <mxCell id="28" parent="C04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- recordSawRekomendasi(int, $ids) : void" vertex="1">
      <mxGeometry height="18" width="345" y="78" as="geometry" />
    </mxCell>
    <mxCell id="S01" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«service»&lt;br&gt;&lt;b&gt;NeedlistSelectionGrouper&lt;/b&gt;" vertex="1">
      <mxGeometry height="105" width="360" x="70" y="520" as="geometry" />
    </mxCell>
    <mxCell id="29" parent="S01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ buildGroups(Collection, array) : array" vertex="1">
      <mxGeometry height="18" width="360" y="42" as="geometry" />
    </mxCell>
    <mxCell id="202" parent="S01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- dominantManufacturer(Collection) : ?string" vertex="1">
      <mxGeometry height="18" width="360" y="60" as="geometry" />
    </mxCell>
    <mxCell id="203" parent="S01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- clusterByVehicleGeneration(array) : array" vertex="1">
      <mxGeometry height="18" width="360" y="78" as="geometry" />
    </mxCell>
    <mxCell id="S02" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«service»&lt;br&gt;&lt;b&gt;SawBatchCalculator&lt;/b&gt;" vertex="1">
      <mxGeometry height="141" width="370" x="450" y="538" as="geometry" />
    </mxCell>
    <mxCell id="30" parent="S02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ calculateForNeedlist(Needlist, array) : array" vertex="1">
      <mxGeometry height="18" width="370" y="42" as="geometry" />
    </mxCell>
    <mxCell id="31" parent="S02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ determineSkipTierKeys(Needlist) : array" vertex="1">
      <mxGeometry height="18" width="370" y="60" as="geometry" />
    </mxCell>
    <mxCell id="204" parent="S02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ calculateGroup(int, array, int) : array" vertex="1">
      <mxGeometry height="18" width="370" y="78" as="geometry" />
    </mxCell>
    <mxCell id="32" parent="S02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- getInquiryDataByCluster(int, array) : array" vertex="1">
      <mxGeometry height="18" width="370" y="96" as="geometry" />
    </mxCell>
    <mxCell id="33" parent="S02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- mergeWithHistoris(array) : array" vertex="1">
      <mxGeometry height="18" width="370" y="114" as="geometry" />
    </mxCell>
    <mxCell id="S03" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«service»&lt;br&gt;&lt;b&gt;SawService&lt;/b&gt;" vertex="1">
      <mxGeometry height="195" width="480" x="850" y="505" as="geometry" />
    </mxCell>
    <mxCell id="34" parent="S03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ calculate(int, ?int, array, ?int, ?string) : array" vertex="1">
      <mxGeometry height="18" width="480" y="42" as="geometry" />
    </mxCell>
    <mxCell id="205" parent="S03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- candidateKey(array) : string" vertex="1">
      <mxGeometry height="18" width="480" y="60" as="geometry" />
    </mxCell>
    <mxCell id="35" parent="S03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- buildMatrix(array, Collection) : array" vertex="1">
      <mxGeometry height="18" width="480" y="78" as="geometry" />
    </mxCell>
    <mxCell id="36" parent="S03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- normalize(array, Collection) : array" vertex="1">
      <mxGeometry height="18" width="480" y="96" as="geometry" />
    </mxCell>
    <mxCell id="37" parent="S03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- weightedSum(array, Collection, array) : Collection" vertex="1">
      <mxGeometry height="18" width="480" y="114" as="geometry" />
    </mxCell>
    <mxCell id="38" parent="S03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- rank(Collection) : Collection" vertex="1">
      <mxGeometry height="18" width="480" y="132" as="geometry" />
    </mxCell>
    <mxCell id="39" parent="S03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- saveToDatabase(int, ?int, Collection, array, array, array, Collection, ?int, ?string) : SawPerhitungan" vertex="1">
      <mxGeometry height="18" width="480" y="150" as="geometry" />
    </mxCell>
    <mxCell id="206" parent="S03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- validateBobot(Collection) : void" vertex="1">
      <mxGeometry height="18" width="480" y="168" as="geometry" />
    </mxCell>
    <mxCell id="E01" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«entity»&lt;br&gt;&lt;b&gt;SawKriteria&lt;/b&gt;" vertex="1">
      <mxGeometry height="240" width="250" x="70" y="780" as="geometry" />
    </mxCell>
    <mxCell id="40" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id : int (PK)" vertex="1">
      <mxGeometry height="18" width="250" y="42" as="geometry" />
    </mxCell>
    <mxCell id="41" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- kode : string" vertex="1">
      <mxGeometry height="18" width="250" y="60" as="geometry" />
    </mxCell>
    <mxCell id="42" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- nama : string" vertex="1">
      <mxGeometry height="18" width="250" y="78" as="geometry" />
    </mxCell>
    <mxCell id="43" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- jenis : enum" vertex="1">
      <mxGeometry height="18" width="250" y="96" as="geometry" />
    </mxCell>
    <mxCell id="44" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- bobot : decimal" vertex="1">
      <mxGeometry height="18" width="250" y="114" as="geometry" />
    </mxCell>
    <mxCell id="45" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- satuan : string" vertex="1">
      <mxGeometry height="18" width="250" y="132" as="geometry" />
    </mxCell>
    <mxCell id="46" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- is_active : bool" vertex="1">
      <mxGeometry height="18" width="250" y="150" as="geometry" />
    </mxCell>
    <mxCell id="47" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- urutan : int" vertex="1">
      <mxGeometry height="18" width="250" y="168" as="geometry" />
    </mxCell>
    <mxCell id="48" parent="E01" style="line;strokeWidth=1;fontSize=14;" value="" vertex="1">
      <mxGeometry height="8" width="250" y="186" as="geometry" />
    </mxCell>
    <mxCell id="49" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ scopeAktif($query)" vertex="1">
      <mxGeometry height="18" width="250" y="194" as="geometry" />
    </mxCell>
    <mxCell id="50" parent="E01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ isCost() : bool   + isBenefit() : bool" vertex="1">
      <mxGeometry height="18" width="250" y="212" as="geometry" />
    </mxCell>
    <mxCell id="E02" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«entity»&lt;br&gt;&lt;b&gt;SawNilaiHistoris&lt;/b&gt;" vertex="1">
      <mxGeometry height="159" width="250" x="380" y="780" as="geometry" />
    </mxCell>
    <mxCell id="51" parent="E02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id : int (PK)" vertex="1">
      <mxGeometry height="18" width="250" y="42" as="geometry" />
    </mxCell>
    <mxCell id="52" parent="E02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- supplier_id : int (FK)" vertex="1">
      <mxGeometry height="18" width="250" y="60" as="geometry" />
    </mxCell>
    <mxCell id="53" parent="E02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- periode_mulai : date" vertex="1">
      <mxGeometry height="18" width="250" y="78" as="geometry" />
    </mxCell>
    <mxCell id="54" parent="E02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- periode_akhir : date" vertex="1">
      <mxGeometry height="18" width="250" y="96" as="geometry" />
    </mxCell>
    <mxCell id="55" parent="E02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- jumlah_transaksi : int" vertex="1">
      <mxGeometry height="18" width="250" y="114" as="geometry" />
    </mxCell>
    <mxCell id="56" parent="E02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- catatan : text" vertex="1">
      <mxGeometry height="18" width="250" y="132" as="geometry" />
    </mxCell>
    <mxCell id="E03" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«entity»&lt;br&gt;&lt;b&gt;SawNilaiHistorisDetail&lt;/b&gt;" vertex="1">
      <mxGeometry height="123" width="260" x="690" y="780" as="geometry" />
    </mxCell>
    <mxCell id="57" parent="E03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id : int (PK)" vertex="1">
      <mxGeometry height="18" width="260" y="42" as="geometry" />
    </mxCell>
    <mxCell id="58" parent="E03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- historis_id : int (FK)" vertex="1">
      <mxGeometry height="18" width="260" y="60" as="geometry" />
    </mxCell>
    <mxCell id="59" parent="E03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- kriteria_id : int (FK)" vertex="1">
      <mxGeometry height="18" width="260" y="78" as="geometry" />
    </mxCell>
    <mxCell id="60" parent="E03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- nilai : decimal" vertex="1">
      <mxGeometry height="18" width="260" y="96" as="geometry" />
    </mxCell>
    <mxCell id="E04" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«entity»&lt;br&gt;&lt;b&gt;SawPerhitungan&lt;/b&gt;" vertex="1">
      <mxGeometry height="213" width="255" x="1010" y="780" as="geometry" />
    </mxCell>
    <mxCell id="61" parent="E04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id : int (PK)" vertex="1">
      <mxGeometry height="18" width="255" y="42" as="geometry" />
    </mxCell>
    <mxCell id="62" parent="E04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- needlist_id : int (FK)" vertex="1">
      <mxGeometry height="18" width="255" y="60" as="geometry" />
    </mxCell>
    <mxCell id="207" parent="E04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_variasi : int (FK)" vertex="1">
      <mxGeometry height="18" width="255" y="78" as="geometry" />
    </mxCell>
    <mxCell id="208" parent="E04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_barang : int (FK)" vertex="1">
      <mxGeometry height="18" width="255" y="96" as="geometry" />
    </mxCell>
    <mxCell id="63" parent="E04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- tier_key : string" vertex="1">
      <mxGeometry height="18" width="255" y="114" as="geometry" />
    </mxCell>
    <mxCell id="65" parent="E04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- bobot_snapshot : json" vertex="1">
      <mxGeometry height="18" width="255" y="132" as="geometry" />
    </mxCell>
    <mxCell id="64" parent="E04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- status : enum" vertex="1">
      <mxGeometry height="18" width="255" y="150" as="geometry" />
    </mxCell>
    <mxCell id="209" parent="E04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- calculated_at : datetime" vertex="1">
      <mxGeometry height="18" width="255" y="168" as="geometry" />
    </mxCell>
    <mxCell id="210" parent="E04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- calculated_by : int (FK)" vertex="1">
      <mxGeometry height="18" width="255" y="186" as="geometry" />
    </mxCell>
    <mxCell id="E05" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«entity»&lt;br&gt;&lt;b&gt;SawPerhitunganDetail&lt;/b&gt;" vertex="1">
      <mxGeometry height="294" width="280" x="1330" y="780" as="geometry" />
    </mxCell>
    <mxCell id="66" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id : int (PK)" vertex="1">
      <mxGeometry height="18" width="280" y="42" as="geometry" />
    </mxCell>
    <mxCell id="67" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- perhitungan_id : int (FK)" vertex="1">
      <mxGeometry height="18" width="280" y="60" as="geometry" />
    </mxCell>
    <mxCell id="68" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- supplier_id : int (FK)" vertex="1">
      <mxGeometry height="18" width="280" y="78" as="geometry" />
    </mxCell>
    <mxCell id="211" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_variasi : int (FK)" vertex="1">
      <mxGeometry height="18" width="280" y="96" as="geometry" />
    </mxCell>
    <mxCell id="71" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- rincian_kriteria : json" vertex="1">
      <mxGeometry height="18" width="280" y="114" as="geometry" />
    </mxCell>
    <mxCell id="69" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- nilai_vi : decimal" vertex="1">
      <mxGeometry height="18" width="280" y="132" as="geometry" />
    </mxCell>
    <mxCell id="70" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- ranking : int" vertex="1">
      <mxGeometry height="18" width="280" y="150" as="geometry" />
    </mxCell>
    <mxCell id="212" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- is_recommended : bool" vertex="1">
      <mxGeometry height="18" width="280" y="168" as="geometry" />
    </mxCell>
    <mxCell id="213" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- sumber_c1 : enum" vertex="1">
      <mxGeometry height="18" width="280" y="186" as="geometry" />
    </mxCell>
    <mxCell id="214" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- sumber_c3 : enum" vertex="1">
      <mxGeometry height="18" width="280" y="204" as="geometry" />
    </mxCell>
    <mxCell id="215" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- has_historis : bool" vertex="1">
      <mxGeometry height="18" width="280" y="222" as="geometry" />
    </mxCell>
    <mxCell id="72" parent="E05" style="line;strokeWidth=1;fontSize=14;" value="" vertex="1">
      <mxGeometry height="8" width="280" y="240" as="geometry" />
    </mxCell>
    <mxCell id="73" parent="E05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ nilai() : float   + norm() : float   + weighted() : float" vertex="1">
      <mxGeometry height="18" width="280" y="248" as="geometry" />
    </mxCell>
    <mxCell id="E06" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«entity»&lt;br&gt;&lt;b&gt;SawRekomendasi&lt;/b&gt;" vertex="1">
      <mxGeometry height="231" width="300" x="1650" y="780" as="geometry" />
    </mxCell>
    <mxCell id="74" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id : int (PK)" vertex="1">
      <mxGeometry height="18" width="300" y="42" as="geometry" />
    </mxCell>
    <mxCell id="75" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- needlist_id : int (FK)" vertex="1">
      <mxGeometry height="18" width="300" y="60" as="geometry" />
    </mxCell>
    <mxCell id="216" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_variasi : int (FK)" vertex="1">
      <mxGeometry height="18" width="300" y="78" as="geometry" />
    </mxCell>
    <mxCell id="217" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- perhitungan_id : int (FK)" vertex="1">
      <mxGeometry height="18" width="300" y="96" as="geometry" />
    </mxCell>
    <mxCell id="76" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- supplier_id_saw : int (FK)" vertex="1">
      <mxGeometry height="18" width="300" y="114" as="geometry" />
    </mxCell>
    <mxCell id="218" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- supplier_id_dipilih : int (FK, nullable)" vertex="1">
      <mxGeometry height="18" width="300" y="132" as="geometry" />
    </mxCell>
    <mxCell id="219" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- mengikuti_rekomendasi : bool" vertex="1">
      <mxGeometry height="18" width="300" y="150" as="geometry" />
    </mxCell>
    <mxCell id="220" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- nilai_vi_terpilih : decimal" vertex="1">
      <mxGeometry height="18" width="300" y="168" as="geometry" />
    </mxCell>
    <mxCell id="221" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- confirmed_at : datetime" vertex="1">
      <mxGeometry height="18" width="300" y="186" as="geometry" />
    </mxCell>
    <mxCell id="77" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- confirmed_by : int (FK)" vertex="1">
      <mxGeometry height="18" width="300" y="204" as="geometry" />
    </mxCell>
    <mxCell id="78" parent="E06" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- sumber : string" vertex="1">
      <mxGeometry height="18" width="250" y="114" as="geometry" />
    </mxCell>
    <mxCell id="P01" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«prasyarat»&lt;br&gt;&lt;b&gt;Supplier&lt;/b&gt;" vertex="1">
      <mxGeometry height="114" width="250" x="70" y="1150" as="geometry" />
    </mxCell>
    <mxCell id="79" parent="P01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_supplier : int (PK)" vertex="1">
      <mxGeometry height="18" width="250" y="42" as="geometry" />
    </mxCell>
    <mxCell id="80" parent="P01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- kode_supplier : string" vertex="1">
      <mxGeometry height="18" width="250" y="60" as="geometry" />
    </mxCell>
    <mxCell id="81" parent="P01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- nama_supplier : string" vertex="1">
      <mxGeometry height="18" width="250" y="78" as="geometry" />
    </mxCell>
    <mxCell id="82" parent="P01" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- alamat : string" vertex="1">
      <mxGeometry height="18" width="250" y="96" as="geometry" />
    </mxCell>
    <mxCell id="P02" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«prasyarat»&lt;br&gt;&lt;b&gt;MBarang&lt;/b&gt;" vertex="1">
      <mxGeometry height="186" width="250" x="380" y="1150" as="geometry" />
    </mxCell>
    <mxCell id="83" parent="P02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_barang : int (PK)" vertex="1">
      <mxGeometry height="18" width="250" y="42" as="geometry" />
    </mxCell>
    <mxCell id="84" parent="P02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- kode_barang : string" vertex="1">
      <mxGeometry height="18" width="250" y="60" as="geometry" />
    </mxCell>
    <mxCell id="85" parent="P02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- nama_barang : string" vertex="1">
      <mxGeometry height="18" width="250" y="78" as="geometry" />
    </mxCell>
    <mxCell id="86" parent="P02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- description : text" vertex="1">
      <mxGeometry height="18" width="250" y="96" as="geometry" />
    </mxCell>
    <mxCell id="87" parent="P02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- is_active : bool" vertex="1">
      <mxGeometry height="18" width="250" y="114" as="geometry" />
    </mxCell>
    <mxCell id="88" parent="P02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_kategori : int" vertex="1">
      <mxGeometry height="18" width="250" y="132" as="geometry" />
    </mxCell>
    <mxCell id="89" parent="P02" style="line;strokeWidth=1;fontSize=14;" value="" vertex="1">
      <mxGeometry height="8" width="250" y="150" as="geometry" />
    </mxCell>
    <mxCell id="90" parent="P02" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ scopeActive($query)" vertex="1">
      <mxGeometry height="18" width="250" y="158" as="geometry" />
    </mxCell>
    <mxCell id="P03" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«prasyarat»&lt;br&gt;&lt;b&gt;Variasi&lt;/b&gt;" vertex="1">
      <mxGeometry height="280" width="260" x="690" y="1150" as="geometry" />
    </mxCell>
    <mxCell id="91" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_variasi : int (PK)" vertex="1">
      <mxGeometry height="18" width="260" y="42" as="geometry" />
    </mxCell>
    <mxCell id="92" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- barcode : string" vertex="1">
      <mxGeometry height="18" width="260" y="60" as="geometry" />
    </mxCell>
    <mxCell id="93" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_barang : int (FK)" vertex="1">
      <mxGeometry height="18" width="260" y="78" as="geometry" />
    </mxCell>
    <mxCell id="94" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- nama_variasi : string" vertex="1">
      <mxGeometry height="18" width="260" y="96" as="geometry" />
    </mxCell>
    <mxCell id="95" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_unit : int" vertex="1">
      <mxGeometry height="18" width="260" y="114" as="geometry" />
    </mxCell>
    <mxCell id="96" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- harga_jual : decimal" vertex="1">
      <mxGeometry height="18" width="260" y="132" as="geometry" />
    </mxCell>
    <mxCell id="97" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- stock : decimal" vertex="1">
      <mxGeometry height="18" width="260" y="150" as="geometry" />
    </mxCell>
    <mxCell id="98" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- status : enum" vertex="1">
      <mxGeometry height="18" width="260" y="168" as="geometry" />
    </mxCell>
    <mxCell id="99" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- part_number : string" vertex="1">
      <mxGeometry height="18" width="260" y="186" as="geometry" />
    </mxCell>
    <mxCell id="100" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- is_active : bool" vertex="1">
      <mxGeometry height="18" width="260" y="204" as="geometry" />
    </mxCell>
    <mxCell id="101" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- tier : enum" vertex="1">
      <mxGeometry height="18" width="260" y="222" as="geometry" />
    </mxCell>
    <mxCell id="102" parent="P03" style="line;strokeWidth=1;fontSize=14;" value="" vertex="1">
      <mxGeometry height="8" width="260" y="240" as="geometry" />
    </mxCell>
    <mxCell id="103" parent="P03" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ scopeActive($query)" vertex="1">
      <mxGeometry height="18" width="260" y="248" as="geometry" />
    </mxCell>
    <mxCell id="P04" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«prasyarat»&lt;br&gt;&lt;b&gt;Needlist&lt;/b&gt;" vertex="1">
      <mxGeometry height="204" width="255" x="1010" y="1150" as="geometry" />
    </mxCell>
    <mxCell id="104" parent="P04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id : int (PK)" vertex="1">
      <mxGeometry height="18" width="255" y="42" as="geometry" />
    </mxCell>
    <mxCell id="105" parent="P04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- kode_needlist : string" vertex="1">
      <mxGeometry height="18" width="255" y="60" as="geometry" />
    </mxCell>
    <mxCell id="106" parent="P04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- user_id : int" vertex="1">
      <mxGeometry height="18" width="255" y="78" as="geometry" />
    </mxCell>
    <mxCell id="107" parent="P04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- status : enum" vertex="1">
      <mxGeometry height="18" width="255" y="96" as="geometry" />
    </mxCell>
    <mxCell id="108" parent="P04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- approval_status : enum" vertex="1">
      <mxGeometry height="18" width="255" y="114" as="geometry" />
    </mxCell>
    <mxCell id="109" parent="P04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- approved_by : int" vertex="1">
      <mxGeometry height="18" width="255" y="132" as="geometry" />
    </mxCell>
    <mxCell id="110" parent="P04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- approved_at : datetime" vertex="1">
      <mxGeometry height="18" width="255" y="150" as="geometry" />
    </mxCell>
    <mxCell id="111" parent="P04" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- approval_notes : text" vertex="1">
      <mxGeometry height="18" width="255" y="168" as="geometry" />
    </mxCell>
    <mxCell id="P05" parent="1" style="swimlane;align=center;startSize=42;container=1;collapsible=0;expand=0;fontSize=14;html=1;rounded=0;" value="«prasyarat»&lt;br&gt;&lt;b&gt;NeedlistItem&lt;/b&gt;" vertex="1">
      <mxGeometry height="186" width="255" x="1330" y="1150" as="geometry" />
    </mxCell>
    <mxCell id="112" parent="P05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id : int (PK)" vertex="1">
      <mxGeometry height="18" width="255" y="42" as="geometry" />
    </mxCell>
    <mxCell id="113" parent="P05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- needlist_id : int (FK)" vertex="1">
      <mxGeometry height="18" width="255" y="60" as="geometry" />
    </mxCell>
    <mxCell id="114" parent="P05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- id_variasi : int (FK)" vertex="1">
      <mxGeometry height="18" width="255" y="78" as="geometry" />
    </mxCell>
    <mxCell id="115" parent="P05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- qty : decimal" vertex="1">
      <mxGeometry height="18" width="255" y="96" as="geometry" />
    </mxCell>
    <mxCell id="116" parent="P05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- status : enum" vertex="1">
      <mxGeometry height="18" width="255" y="114" as="geometry" />
    </mxCell>
    <mxCell id="117" parent="P05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="- is_reference : bool" vertex="1">
      <mxGeometry height="18" width="255" y="132" as="geometry" />
    </mxCell>
    <mxCell id="118" parent="P05" style="line;strokeWidth=1;fontSize=14;" value="" vertex="1">
      <mxGeometry height="8" width="255" y="150" as="geometry" />
    </mxCell>
    <mxCell id="119" parent="P05" style="text;align=left;verticalAlign=top;spacingLeft=6;overflow=hidden;rotatable=0;fontSize=14;html=1;" value="+ getSupplierAttribute() : Supplier|null" vertex="1">
      <mxGeometry height="28" width="255" y="158" as="geometry" />
    </mxCell>
    <mxCell id="A01" edge="1" parent="1" source="P04" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX=1;exitY=0.5;entryX=0;entryY=0.5;fontSize=14;" target="P05" value="">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="120" connectable="0" parent="A01" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="121" connectable="0" parent="A01" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A02" edge="1" parent="1" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX=0.5;exitY=1;fontSize=14;entryX=1;entryY=0.75;entryDx=0;entryDy=0;" target="100" value="">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="1477.51" y="1367.51" />
        </Array>
        <mxPoint x="1477.5" y="1336" as="sourcePoint" />
        <mxPoint x="970" y="1367.5" as="targetPoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="122" connectable="0" parent="A02" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint y="23" as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="123" connectable="0" parent="A02" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint y="23" as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A03" edge="1" parent="1" source="P03" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX=0;exitY=0.5;entryX=1;entryY=0.5;fontSize=14;" target="P02" value="">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="124" connectable="0" parent="A03" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="125" connectable="0" parent="A03" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A04" edge="1" parent="1" source="E04" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX=0.5;exitY=1;entryX=0.5;entryY=0;fontSize=14;" target="P04" value="">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="126" connectable="0" parent="A04" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="127" connectable="0" parent="A04" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A05" edge="1" parent="1" source="E06" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;entryX=0.8;entryY=0;fontSize=14;exitX=0.5;exitY=1;exitDx=0;exitDy=0;" target="P04" value="">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="1775" y="1120.03" />
          <mxPoint x="1214.03" y="1120.03" />
        </Array>
        <mxPoint x="1810" y="970" as="sourcePoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="128" connectable="0" parent="A05" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint x="5" y="-50" as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="129" connectable="0" parent="A05" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint x="-33" y="20" as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A06" edge="1" parent="1" source="E02" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX=0.3;exitY=1;entryX=0.5;entryY=0;entryDx=0;entryDy=0;fontSize=14;" target="P01" value="">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="455" y="1060" />
          <mxPoint x="195" y="1060" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="130" connectable="0" parent="A06" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="131" connectable="0" parent="A06" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A07" edge="1" parent="1" source="52" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX=1;exitY=0;exitDx=0;exitDy=0;fontSize=14;entryX=0;entryY=0;entryDx=0;entryDy=0;" target="58" value="">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="670" y="840" />
          <mxPoint x="670" y="840" />
        </Array>
        <mxPoint x="660" y="890" as="targetPoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="132" connectable="0" parent="A07" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="133" connectable="0" parent="A07" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A08" edge="1" parent="1" source="E03" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX=0.424;exitY=0.003;exitDx=0;exitDy=0;exitPerimeter=0;fontSize=14;" target="E01" value="">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="800.19" y="755" />
          <mxPoint x="239.95" y="755" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="134" connectable="0" parent="A08" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint y="25" as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="135" connectable="0" parent="A08" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint y="25" as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A09" edge="1" parent="1" source="E04" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX=1;exitY=0.5;entryX=0;entryY=0.5;fontSize=14;" target="E05" value="">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="136" connectable="0" parent="A09" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="137" connectable="0" parent="A09" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A10" edge="1" parent="1" source="E05" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX=0.5;exitY=1;entryX=0.684;entryY=0.022;entryDx=0;entryDy=0;exitDx=0;exitDy=0;entryPerimeter=0;fontSize=14;" target="P01" value="">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="1470" y="984" />
          <mxPoint x="1470" y="1090" />
          <mxPoint x="240" y="1090" />
          <mxPoint x="240" y="1150" />
          <mxPoint x="241" y="1150" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="138" connectable="0" parent="A10" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="139" connectable="0" parent="A10" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="A11" edge="1" parent="1" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;fontSize=14;" value="">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="1760" y="1110" />
          <mxPoint x="277.52" y="1110" />
        </Array>
        <mxPoint x="1760" y="920" as="sourcePoint" />
        <mxPoint x="277.5" y="1147.15" as="targetPoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="140" connectable="0" parent="A11" style="resizable=0;html=1;align=left;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="*" vertex="1">
      <mxGeometry relative="1" x="-0.8" as="geometry">
        <mxPoint x="-20" y="-141" as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="141" connectable="0" parent="A11" style="resizable=0;html=1;align=right;verticalAlign=bottom;labelBackgroundColor=none;fontSize=14;" value="1" vertex="1">
      <mxGeometry relative="1" x="0.8" as="geometry">
        <mxPoint as="offset" />
      </mxGeometry>
    </mxCell>
    <mxCell id="D01" edge="1" parent="1" source="B01" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.5;exitY=1;entryX=0.2;entryY=0;" target="C01" value="«uses»">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="D02" edge="1" parent="1" source="B02" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.5;exitY=1;entryX=0.8;entryY=0;" target="C01" value="«uses»">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="D03" edge="1" parent="1" source="B03" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.5;exitY=1;entryX=0.25;entryY=0;" target="C02" value="«uses»">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="D04" edge="1" parent="1" source="B04" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.5;exitY=1;entryX=0.75;entryY=0;" target="C02" value="«uses»">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="D05" edge="1" parent="1" source="B05" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.3;exitY=1;entryX=0.2;entryY=0;" target="C03" value="«uses»">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="D06" edge="1" parent="1" source="B06" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.5;exitY=1;entryX=0.7;entryY=0;" target="C03" value="«uses»">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="D07" edge="1" parent="1" source="B07" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.25;exitY=1;entryX=0.809;entryY=-0.024;entryDx=0;entryDy=0;entryPerimeter=0;exitDx=0;exitDy=0;" target="C03" value="«uses»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="1371.21" y="140" />
          <mxPoint x="1141.21" y="140" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D08" edge="1" parent="1" source="B07" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.5;exitY=1;entryX=0.3;entryY=0;" target="C04" value="«uses»">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="D09" edge="1" parent="1" source="25" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.25;exitY=1;entryX=0.48;entryY=-0.003;entryDx=0;entryDy=0;exitDx=0;exitDy=0;entryPerimeter=0;" target="S01" value="«depends»">
      <mxGeometry relative="1" x="0.1141" as="geometry">
        <mxPoint as="offset" />
        <Array as="points">
          <mxPoint x="940" y="440.05" />
          <mxPoint x="228.38" y="440.05" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D10" edge="1" parent="1" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;entryX=0.75;entryY=0;entryDx=0;entryDy=0;" target="S02" value="«depends»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="980" y="480" />
          <mxPoint x="712.5" y="480" />
        </Array>
        <mxPoint x="980" y="280" as="sourcePoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="D11" edge="1" parent="1" source="28" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.45;exitY=1.082;exitDx=0;exitDy=0;exitPerimeter=0;entryX=0.576;entryY=-0.037;entryDx=0;entryDy=0;entryPerimeter=0;" target="S01" value="«depends»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="1474.29" y="460.05" />
          <mxPoint x="260" y="460.05" />
        </Array>
        <mxPoint x="308.26" y="490" as="targetPoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="D12" edge="1" parent="1" source="S02" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=1;exitY=0.5;entryX=0;entryY=0.5;" target="S03" value="«depends»">
      <mxGeometry relative="1" x="-0.6923" y="-11" as="geometry">
        <mxPoint as="offset" />
        <Array as="points">
          <mxPoint x="830" y="599.48" />
          <mxPoint x="830" y="584.52" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D13" edge="1" parent="1" source="C01" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.3;exitY=1;entryX=0.3;entryY=0;" target="E01" value="«uses»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="169" y="440" />
          <mxPoint x="50" y="440" />
          <mxPoint x="50" y="700" />
          <mxPoint x="145" y="700" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D14" edge="1" parent="1" source="C02" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.3;exitY=1;" target="E02" value="«uses»">
      <mxGeometry relative="1" x="0.033" y="-10" as="geometry">
        <mxPoint as="offset" />
        <Array as="points">
          <mxPoint x="538" y="510" />
          <mxPoint x="440" y="510" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D15" edge="1" parent="1" source="C02" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.1;exitY=1;" value="«uses»">
      <mxGeometry relative="1" x="-0.1198" y="-10" as="geometry">
        <mxPoint as="offset" />
        <Array as="points">
          <mxPoint x="466" y="490" />
          <mxPoint x="410" y="490" />
          <mxPoint x="410" y="700" />
          <mxPoint x="180" y="700" />
          <mxPoint x="180" y="780" />
        </Array>
        <mxPoint x="180" y="780" as="targetPoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="D16" edge="1" parent="1" source="C02" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.7;exitY=1;entryX=0.5;entryY=0;entryDx=0;entryDy=0;" target="E03" value="«uses»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="682" y="489.98" />
          <mxPoint x="820" y="489.98" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D17" edge="1" parent="1" source="C03" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.5;exitY=1;entryX=0.3;entryY=0;" target="P04" value="«uses»">
      <mxGeometry relative="1" x="-0.2281" as="geometry">
        <mxPoint as="offset" />
        <Array as="points">
          <mxPoint x="1030" y="350" />
          <mxPoint x="1230" y="350" />
          <mxPoint x="1230" y="760" />
          <mxPoint x="980" y="760" />
          <mxPoint x="980" y="1020" />
          <mxPoint x="1086.5" y="1020" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D18" edge="1" parent="1" source="28" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.5;exitY=1;entryX=0.7;entryY=0;exitDx=0;exitDy=0;" target="P04" value="«uses»">
      <mxGeometry relative="1" x="-0.1175" as="geometry">
        <mxPoint as="offset" />
        <Array as="points">
          <mxPoint x="1491.57" y="490" />
          <mxPoint x="1290" y="490" />
          <mxPoint x="1290" y="1010" />
          <mxPoint x="1188.57" y="1010" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D19" edge="1" parent="1" source="C04" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=1;exitY=0.5;entryX=0.5;entryY=0;" target="E06" value="«creates»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="1775" y="213" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D20" edge="1" parent="1" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;entryX=0.2;entryY=0;" target="P04" value="«reads»">
      <mxGeometry relative="1" x="0.4846" y="10" as="geometry">
        <mxPoint as="offset" />
        <Array as="points">
          <mxPoint x="260" y="680.06" />
          <mxPoint x="660" y="680.06" />
          <mxPoint x="660" y="1050.06" />
          <mxPoint x="1061.06" y="1050.06" />
        </Array>
        <mxPoint x="260" y="589" as="sourcePoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="D21" edge="1" parent="1" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;entryX=0.3;entryY=0;" target="P05" value="«reads»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="330" y="670" />
          <mxPoint x="670" y="670" />
          <mxPoint x="670" y="990" />
          <mxPoint x="1406.5" y="990" />
        </Array>
        <mxPoint x="330" y="589" as="sourcePoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="D22" edge="1" parent="1" source="S01" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.5;exitY=1;entryX=0.3;entryY=0;exitDx=0;exitDy=0;" target="P03" value="«reads»">
      <mxGeometry relative="1" x="0.6381" as="geometry">
        <mxPoint as="offset" />
        <Array as="points">
          <mxPoint x="240" y="589" />
          <mxPoint x="240" y="690.06" />
          <mxPoint x="650" y="690.06" />
          <mxPoint x="650" y="1060.06" />
          <mxPoint x="768" y="1060.06" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D23" edge="1" parent="1" source="S02" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.3;exitY=1;entryX=0.6;entryY=0;" target="E02" value="«reads»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="555" y="700" />
          <mxPoint x="530" y="700" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D24" edge="1" parent="1" source="S02" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.7;exitY=1;entryX=0.137;entryY=0.003;entryDx=0;entryDy=0;entryPerimeter=0;" target="E03" value="«reads»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="695" y="710" />
          <mxPoint x="725.6" y="710" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D25" edge="1" parent="1" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;" value="«reads»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="490" y="710" />
          <mxPoint x="200" y="710" />
          <mxPoint x="200" y="779.08" />
        </Array>
        <mxPoint x="490" y="650" as="sourcePoint" />
        <mxPoint x="200" y="779.11" as="targetPoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="D26" edge="1" parent="1" source="33" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.882;exitY=1.022;entryX=0.2;entryY=0;exitDx=0;exitDy=0;exitPerimeter=0;" target="E04" value="«writes»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="758.75" y="720" />
          <mxPoint x="760" y="720" />
          <mxPoint x="760" y="740" />
          <mxPoint x="1060" y="740" />
          <mxPoint x="1060" y="780" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D27" edge="1" parent="1" source="S03" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.3;exitY=1;entryX=0.7;entryY=0;" target="E04" value="«creates»">
      <mxGeometry relative="1" as="geometry" />
    </mxCell>
    <mxCell id="D28" edge="1" parent="1" source="S03" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.8;exitY=1;entryX=0.3;entryY=0;" target="E05" value="«creates»">
      <mxGeometry relative="1" x="-0.1875" as="geometry">
        <mxPoint as="offset" />
        <Array as="points">
          <mxPoint x="1134" y="710.06" />
          <mxPoint x="1408" y="710.06" />
        </Array>
      </mxGeometry>
    </mxCell>
    <mxCell id="D29" edge="1" parent="1" source="39" style="edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;dashed=1;endArrow=open;endFill=0;fontSize=14;exitX=0.25;exitY=1;exitDx=0;exitDy=0;entryX=0.604;entryY=-0.004;entryDx=0;entryDy=0;entryPerimeter=0;" target="E01" value="«reads»">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="938.8" y="670" />
          <mxPoint x="940" y="670" />
          <mxPoint x="940" y="730" />
          <mxPoint x="221" y="730" />
        </Array>
        <mxPoint x="170" y="780" as="targetPoint" />
      </mxGeometry>
    </mxCell>
    <mxCell id="0JeQUQQEfLzXBxlyeyAU-141" edge="1" parent="1" source="40" style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;fontSize=14;entryX=0;entryY=0;entryDx=0;entryDy=0;" target="52" value="">
      <mxGeometry relative="1" as="geometry">
        <Array as="points">
          <mxPoint x="350" y="840" />
          <mxPoint x="350" y="840" />
        </Array>
      </mxGeometry>
    </mxCell>
  </root>
</mxGraphModel>
