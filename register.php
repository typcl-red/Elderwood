<div class="form-group" id="employerIdGroup" style="display: none;">
    <label for="employer_id">Employer ID (Seller's ID)</label>
    <input type="text" id="employer_id" name="employer_id" class="form-control">
</div>

<div class="form-group">
    <label for="role">Role</label>
    <select id="role" name="role" class="form-control" required onchange="toggleEmployerField()">
        <option value="Buyer">Buyer</option>
        <option value="Seller">Seller</option>
        <option value="Laborer">Laborer</option>
    </select>
</div>

<script>
function toggleEmployerField() {
    const role = document.getElementById('role').value;
    const employerField = document.getElementById('employerIdGroup');
    employerField.style.display = role === 'Laborer' ? 'block' : 'none';
    document.getElementById('employer_id').required = role === 'Laborer';
}
</script>