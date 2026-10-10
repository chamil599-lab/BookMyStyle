import React, { useState, useEffect } from "react";
import api from "../utils/api";

export default function FilterSalon({ onSalonsFiltered }) {
  const [categories, setCategories] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState("");
  const [selectedDepartment, setSelectedDepartment] = useState("");

  // Fetch categories and departments on mount
  useEffect(() => {
    api.get("/categories")
      .then((res) => setCategories(res.data))
      .catch((err) => console.error("Error fetching categories:", err));

    api.get("/departments")
      .then((res) => setDepartments(res.data))
      .catch((err) => console.error("Error fetching departments:", err));
  }, []);

  // Fetch filtered salons when filters change
  useEffect(() => {
    let url = "/salons?";
    if (selectedCategory) url += `category_id=${selectedCategory}&`;
    if (selectedDepartment) url += `department_id=${selectedDepartment}&`; // Use department ID

    api.get(url)
      .then((res) => onSalonsFiltered(res.data))
      .catch((err) => console.error("Error fetching salons:", err));
  }, [selectedCategory, selectedDepartment]);

  return (
    <div className="flex flex-col md:flex-row gap- mb-6">
      {/* Department Filter */}
      <select
        className="p-2 border rounded"
        value={selectedDepartment}
        onChange={(e) => setSelectedDepartment(e.target.value)}
      >
        <option value="">All Departments</option>
        {departments.map((dep) => (
          <option key={dep.id} value={dep.id}> {/* Use ID instead of name */}
            {dep.name}
          </option>
        ))}
      </select>
    </div>
  );
}
